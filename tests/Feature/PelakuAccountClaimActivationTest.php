<?php

namespace Tests\Feature;

use App\Models\Umkm\UmkmAccountClaim;
use App\Models\Umkm\UmkmAccountClaimEvent;
use App\Models\Umkm\UmkmClaimActivationChallenge;
use App\Models\Umkm\UmkmUserLink;
use App\Models\User;
use App\Services\PelakuUmkm\AccountClaimActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PelakuAccountClaimActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->updateOrInsert(
            ['code' => 'pelaku_umkm'],
            [
                'name' => 'Pelaku UMKM',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->seedImmutableUmkmFixture(
            'LSS-TEST-001',
            'UMKM Fixture Immutable Satu'
        );
        $this->seedImmutableUmkmFixture(
            'LSS-TEST-002',
            'UMKM Fixture Immutable Dua'
        );

        Mail::fake();
    }

    public function test_approval_and_activation_create_verified_binding_without_mutating_umkm(): void
    {
        $service = app(AccountClaimActivationService::class);
        $request = $this->request('/pelaku/claim');

        $sourceBefore = $this->snapshotUmkm('LSS-TEST-001');

        $claim = $service->submitSelfClaim([
            'umkm_code' => 'LSS-TEST-001',
            'applicant_name' => 'Pelaku Uji',
            'applicant_email' => 'pelaku@example.test',
        ], $request);

        $this->assertSame(UmkmAccountClaim::STATUS_PENDING_REVIEW, $claim->status);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('umkm_user_links', 0);

        $reviewer = $this->createReviewer('dinas@example.test');

        $result = $service->review(
            $reviewer,
            $claim,
            'approve',
            'Keterkaitan telah diverifikasi.',
            $request
        );

        $this->assertTrue($result['delivery_ok']);
        $this->assertSame(
            UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION,
            $result['claim']->status
        );
        $this->assertDatabaseCount('umkm_user_links', 0);

        $activated = $this->activateClaim(
            $service,
            $claim->fresh(),
            $request,
            'PasswordDibuatPelaku123'
        );

        $this->assertSame(UmkmAccountClaim::STATUS_ACTIVATED, $activated->status);
        $this->assertDatabaseCount('umkm_user_links', 1);

        $pelaku = User::query()
            ->where('email', 'pelaku@example.test')
            ->firstOrFail();

        $this->assertTrue((bool) $pelaku->is_active);
        $this->assertTrue(Hash::check('PasswordDibuatPelaku123', $pelaku->password));
        $this->assertTrue($pelaku->hasRole('pelaku_umkm'));

        $this->assertDatabaseHas('user_identity_credentials', [
            'user_id' => $pelaku->id,
            'identifier_type' => 'email',
            'is_active' => 1,
            'login_enabled' => 1,
        ]);

        $binding = UmkmUserLink::query()->firstOrFail();

        $this->assertSame((int) $claim->umkm_id, (int) $binding->umkm_id);
        $this->assertSame((int) $pelaku->id, (int) $binding->user_id);
        $this->assertSame('owner', $binding->relationship_type);
        $this->assertFalse((bool) $binding->is_primary);
        $this->assertSame((int) $claim->id, (int) $binding->source_claim_id);
        $this->assertSame(
            UmkmUserLink::BINDING_SOURCE_ACCOUNT_CLAIM_ACTIVATION,
            $binding->binding_source
        );
        $this->assertSame(
            UmkmUserLink::VERIFICATION_VERIFIED,
            $binding->verification_status
        );
        $this->assertTrue((bool) $binding->is_active);
        $this->assertNotNull($binding->verified_at);
        $this->assertSame((int) $reviewer->id, (int) $binding->verified_by_user_id);
        $this->assertNull($binding->revoked_at);
        $this->assertTrue($binding->isActiveVerified());

        $event = UmkmAccountClaimEvent::query()
            ->where('claim_id', $claim->id)
            ->where('event_type', 'account_activation_completed')
            ->latest('id')
            ->firstOrFail();

        $this->assertTrue((bool) ($event->event_detail['ownership_binding_created'] ?? false));
        $this->assertSame(
            (int) $binding->id,
            (int) ($event->event_detail['ownership_binding_id'] ?? 0)
        );

        $sourceAfter = $this->snapshotUmkm('LSS-TEST-001');
        $this->assertEquals($sourceBefore, $sourceAfter);
    }

    public function test_one_active_pelaku_account_can_bind_multiple_umkm(): void
    {
        $service = app(AccountClaimActivationService::class);
        $request = $this->request('/pelaku/claim');
        $reviewer = $this->createReviewer('multi-dinas@example.test');

        $first = $service->submitSelfClaim([
            'umkm_code' => 'LSS-TEST-001',
            'applicant_name' => 'Pelaku Multi',
            'applicant_email' => 'multi@example.test',
        ], $request);

        $service->review($reviewer, $first, 'approve', 'UMKM pertama terverifikasi.', $request);
        $firstActivated = $this->activateClaim(
            $service,
            $first->fresh(),
            $request,
            'PasswordPelakuMulti123'
        );

        $pelaku = User::query()
            ->where('email', 'multi@example.test')
            ->firstOrFail();

        $second = $service->submitSelfClaim([
            'umkm_code' => 'LSS-TEST-002',
            'applicant_name' => 'Pelaku Multi',
            'applicant_email' => 'multi@example.test',
        ], $request);

        $service->review($reviewer, $second, 'approve', 'UMKM kedua terverifikasi.', $request);
        $secondActivated = $this->activateClaim(
            $service,
            $second->fresh(),
            $request,
            null
        );

        $this->assertSame((int) $pelaku->id, (int) $firstActivated->activated_user_id);
        $this->assertSame((int) $pelaku->id, (int) $secondActivated->activated_user_id);
        $this->assertDatabaseCount('umkm_user_links', 2);

        $links = UmkmUserLink::query()
            ->activeVerified()
            ->where('user_id', $pelaku->id)
            ->orderBy('umkm_id')
            ->get();

        $this->assertCount(2, $links);
        $this->assertSame(
            [$first->id, $second->id],
            $links->pluck('source_claim_id')->sort()->values()->all()
        );
        $this->assertTrue($links->every(fn (UmkmUserLink $link): bool => ! $link->is_primary));
    }

    public function test_duplicate_verified_binding_blocks_new_claim_for_same_umkm(): void
    {
        $service = app(AccountClaimActivationService::class);
        $request = $this->request('/pelaku/claim');
        $reviewer = $this->createReviewer('duplicate-dinas@example.test');

        $first = $service->submitSelfClaim([
            'umkm_code' => 'LSS-TEST-001',
            'applicant_name' => 'Pelaku Duplikat',
            'applicant_email' => 'duplicate@example.test',
        ], $request);

        $service->review($reviewer, $first, 'approve', 'Binding pertama valid.', $request);
        $this->activateClaim(
            $service,
            $first->fresh(),
            $request,
            'PasswordDuplikat123'
        );

        $this->assertDatabaseCount('umkm_user_links', 1);

        try {
            $service->submitSelfClaim([
                'umkm_code' => 'LSS-TEST-001',
                'applicant_name' => 'Pelaku Duplikat',
                'applicant_email' => 'duplicate@example.test',
            ], $request);

            $this->fail('Duplicate verified binding should block a new claim.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('claim', $exception->errors());
        }

        $this->assertDatabaseCount('umkm_account_claims', 1);
        $this->assertDatabaseCount('umkm_user_links', 1);
    }

    public function test_rejection_and_resubmission_preserve_history(): void
    {
        $service = app(AccountClaimActivationService::class);
        $request = $this->request('/pelaku/claim');
        $reviewer = $this->createReviewer('reviewer@example.test');

        $first = $service->submitSelfClaim([
            'umkm_code' => 'LSS-TEST-001',
            'applicant_name' => 'Pelaku Uji',
            'applicant_email' => 'pelaku2@example.test',
        ], $request);

        $service->review(
            $reviewer,
            $first,
            'reject',
            'Bukti keterkaitan belum cukup.',
            $request
        );

        $second = $service->submitSelfClaim([
            'umkm_code' => 'LSS-TEST-001',
            'applicant_name' => 'Pelaku Uji',
            'applicant_email' => 'pelaku2@example.test',
        ], $request);

        $this->assertSame($first->id, $second->resubmission_of_id);
        $this->assertSame(UmkmAccountClaim::STATUS_REJECTED, $first->fresh()->status);
        $this->assertDatabaseCount('umkm_account_claims', 2);
        $this->assertDatabaseCount('umkm_user_links', 0);
        $this->assertGreaterThanOrEqual(
            3,
            DB::table('umkm_account_claim_events')->count()
        );
    }

    public function test_dinas_invite_remains_unbound_until_activation(): void
    {
        $service = app(AccountClaimActivationService::class);
        $request = $this->request('/admin-dinas/account-claims/invite');
        $reviewer = $this->createReviewer('dinas-invite@example.test');

        $result = $service->createDinasInvite($reviewer, [
            'umkm_code' => 'LSS-TEST-001',
            'applicant_name' => 'Pelaku Undangan',
            'applicant_email' => 'undangan@example.test',
            'review_note' => 'Diverifikasi langsung oleh Dinas.',
        ], $request);

        $this->assertTrue($result['delivery_ok']);
        $this->assertSame(
            UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION,
            $result['claim']->status
        );
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('umkm_user_links', 0);
        $this->assertDatabaseCount('umkm_claim_activation_challenges', 1);
    }

    private function activateClaim(
        AccountClaimActivationService $service,
        UmkmAccountClaim $claim,
        Request $request,
        ?string $password
    ): UmkmAccountClaim {
        $knownToken = str_repeat((string) (($claim->id % 9) + 1), 64);
        $knownOtp = str_pad((string) (123450 + $claim->id), 6, '0', STR_PAD_LEFT);

        $challenge = UmkmClaimActivationChallenge::query()
            ->where('claim_id', $claim->id)
            ->latest('id')
            ->firstOrFail();

        $challenge->forceFill([
            'challenge_token_hash' => hash('sha256', $knownToken),
            'otp_hash' => hash_hmac(
                'sha256',
                $claim->claim_reference.'|'.$knownOtp,
                (string) config('app.key')
            ),
        ])->save();

        $payload = [
            'activation_token' => $knownToken,
            'otp' => $knownOtp,
        ];

        if ($password !== null) {
            $payload['password'] = $password;
            $payload['password_confirmation'] = $password;
        }

        return $service->activate($claim, $payload, $request);
    }

    private function createReviewer(string $email): User
    {
        return User::query()->create([
            'name' => 'Admin Dinas',
            'email' => $email,
            'password' => 'ReviewerPassword123',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function request(string $path): Request
    {
        return Request::create($path, 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]);
    }

    private function seedImmutableUmkmFixture(string $code, string $name): void
    {
        $this->assertTrue(Schema::hasTable('umkms'));
        $this->assertTrue(Schema::hasColumn('umkms', 'umkm_code'));
        $this->assertTrue(Schema::hasColumn('umkms', 'business_name'));

        DB::table('umkms')->insert([
            'umkm_code' => $code,
            'business_name' => $name,
        ]);
    }

    private function snapshotUmkm(string $code): array
    {
        $row = DB::table('umkms')->where('umkm_code', $code)->first();
        $this->assertNotNull($row);

        return (array) $row;
    }
}