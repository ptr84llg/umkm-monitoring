<?php

namespace Tests\Feature;

use App\Models\Umkm\UmkmAccountClaim;
use App\Models\Umkm\UmkmClaimActivationChallenge;
use App\Models\User;
use App\Services\PelakuUmkm\AccountClaimActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
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

        $this->seedImmutableUmkmFixture();

        Mail::fake();
    }

    public function test_approval_and_activation_never_create_ownership_binding_or_mutate_umkm(): void
    {
        $service = app(AccountClaimActivationService::class);
        $request = Request::create('/pelaku/claim', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]);

        $sourceBeforeRow = DB::table('umkms')
            ->where('umkm_code', 'LSS-TEST-001')
            ->first();
        $this->assertNotNull($sourceBeforeRow);
        $sourceBefore = (array) $sourceBeforeRow;

        $claim = $service->submitSelfClaim([
            'umkm_code' => 'LSS-TEST-001',
            'applicant_name' => 'Pelaku Uji',
            'applicant_email' => 'pelaku@example.test',
        ], $request);

        $this->assertSame(UmkmAccountClaim::STATUS_PENDING_REVIEW, $claim->status);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('umkm_user_links', 0);

        $reviewer = User::query()->create([
            'name' => 'Admin Dinas',
            'email' => 'dinas@example.test',
            'password' => 'ReviewerPassword123',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $result = $service->review($reviewer, $claim, 'approve', 'Keterkaitan telah diverifikasi.', $request);

        $this->assertTrue($result['delivery_ok']);
        $this->assertSame(
            UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION,
            $result['claim']->status
        );
        $this->assertDatabaseCount('umkm_user_links', 0);
        $this->assertDatabaseCount('users', 1);

        $knownToken = str_repeat('t', 64);
        $knownOtp = '123456';
        $challenge = UmkmClaimActivationChallenge::query()->latest('id')->firstOrFail();
        $challenge->forceFill([
            'challenge_token_hash' => hash('sha256', $knownToken),
            'otp_hash' => hash_hmac(
                'sha256',
                $claim->claim_reference.'|'.$knownOtp,
                (string) config('app.key')
            ),
        ])->save();

        $activated = $service->activate($claim->fresh(), [
            'activation_token' => $knownToken,
            'otp' => $knownOtp,
            'password' => 'PasswordDibuatPelaku123',
            'password_confirmation' => 'PasswordDibuatPelaku123',
        ], $request);

        $this->assertSame(UmkmAccountClaim::STATUS_ACTIVATED, $activated->status);
        $this->assertDatabaseCount('umkm_user_links', 0);

        $pelaku = User::query()->where('email', 'pelaku@example.test')->firstOrFail();
        $this->assertTrue((bool) $pelaku->is_active);
        $this->assertTrue(Hash::check('PasswordDibuatPelaku123', $pelaku->password));
        $this->assertTrue($pelaku->hasRole('pelaku_umkm'));

        $this->assertDatabaseHas('user_identity_credentials', [
            'user_id' => $pelaku->id,
            'identifier_type' => 'email',
            'is_active' => 1,
            'login_enabled' => 1,
        ]);

        $sourceAfterRow = DB::table('umkms')
            ->where('umkm_code', 'LSS-TEST-001')
            ->first();
        $this->assertNotNull($sourceAfterRow);
        $sourceAfter = (array) $sourceAfterRow;

        $this->assertEquals($sourceBefore, $sourceAfter);
    }

    public function test_rejection_and_resubmission_preserve_history(): void
    {
        $service = app(AccountClaimActivationService::class);
        $request = Request::create('/pelaku/claim', 'POST');
        $reviewer = User::query()->create([
            'name' => 'Admin Dinas',
            'email' => 'reviewer@example.test',
            'password' => 'ReviewerPassword123',
            'is_active' => true,
        ]);

        $first = $service->submitSelfClaim([
            'umkm_code' => 'LSS-TEST-001',
            'applicant_name' => 'Pelaku Uji',
            'applicant_email' => 'pelaku2@example.test',
        ], $request);

        $service->review($reviewer, $first, 'reject', 'Bukti keterkaitan belum cukup.', $request);

        $second = $service->submitSelfClaim([
            'umkm_code' => 'LSS-TEST-001',
            'applicant_name' => 'Pelaku Uji',
            'applicant_email' => 'pelaku2@example.test',
        ], $request);

        $this->assertSame($first->id, $second->resubmission_of_id);
        $this->assertSame(UmkmAccountClaim::STATUS_REJECTED, $first->fresh()->status);
        $this->assertDatabaseCount('umkm_account_claims', 2);
        $this->assertGreaterThanOrEqual(3, DB::table('umkm_account_claim_events')->count());
    }

    public function test_dinas_invite_is_approved_pending_activation_without_user_or_binding(): void
    {
        $service = app(AccountClaimActivationService::class);
        $request = Request::create('/admin-dinas/account-claims/invite', 'POST');
        $reviewer = User::query()->create([
            'name' => 'Admin Dinas',
            'email' => 'dinas-invite@example.test',
            'password' => 'ReviewerPassword123',
            'is_active' => true,
        ]);

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

    private function seedImmutableUmkmFixture(): void
    {
        $this->assertTrue(Schema::hasTable('umkms'));
        $this->assertTrue(Schema::hasColumn('umkms', 'umkm_code'));
        $this->assertTrue(Schema::hasColumn('umkms', 'business_name'));

        DB::table('umkms')->insert([
            'umkm_code' => 'LSS-TEST-001',
            'business_name' => 'UMKM Fixture Immutable',
        ]);
    }
}
