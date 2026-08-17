<?php

namespace Tests\Feature;

use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmProfileOverrideRevision;
use App\Models\Umkm\UmkmUpdateSubmission;
use App\Models\User;
use App\Services\AdminDinas\UmkmOfficialService;
use App\Services\Audit\AuditLogger;
use App\Services\PelakuUmkm\EffectiveUmkmProfileService;
use App\Services\Proposal\UmkmProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PelakuProfileOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelaku_submission_and_approval_create_effective_override_without_source_mutation(): void
    {
        [$pelaku, $umkm] = $this->createVerifiedPelakuFixture();
        $sourceBefore = $this->sourceSnapshot($umkm->id);

        $this->actingAs($pelaku)
            ->post(route('pelaku-umkm.profile-change.store', $umkm), [
                'business_name' => 'Nama Efektif Baru',
                'established_date' => '2020-01-02',
                'employee_count' => 7,
                'marketing_method_id' => null,
            ])
            ->assertRedirect();

        $submission = UmkmUpdateSubmission::query()->firstOrFail();
        $this->assertSame('diajukan', $submission->status_data);
        $this->assertSame('Nama Efektif Baru', $submission->new_data['business_name']);
        $this->assertArrayNotHasKey('quality_status', $submission->new_data);
        $this->assertSame($sourceBefore, $this->sourceSnapshot($umkm->id));

        $reviewer = User::query()->create([
            'name' => 'Reviewer Dinas',
            'email' => 'reviewer10d@example.test',
            'password' => 'ReviewerPassword123',
            'is_active' => true,
        ]);

        $request = Request::create('/internal-review', 'POST');
        $request->setUserResolver(fn () => $reviewer);

        $updated = app(UmkmProposalService::class)->reviewProposal(
            $submission,
            'disetujui',
            'Data pendukung sesuai.',
            $reviewer->id,
            $request,
            app(AuditLogger::class)
        );

        $this->assertSame('disetujui', $updated->status_data);
        $this->assertSame(1, DB::table('umkm_profile_override_revisions')->count());
        $this->assertSame(1, DB::table('umkm_current_profile_overrides')->count());
        $this->assertSame($sourceBefore, $this->sourceSnapshot($umkm->id));

        $resolved = app(EffectiveUmkmProfileService::class)->resolve($umkm->fresh());
        $this->assertSame('Nama Sumber Tetap', $resolved['source']['business_name']);
        $this->assertSame('Nama Efektif Baru', $resolved['effective']['business_name']);
        $this->assertContains('business_name', $resolved['overridden_fields']);
        $this->assertNotNull($resolved['provenance']);
    }

    public function test_system_managed_fields_are_rejected_and_other_umkm_is_isolated(): void
    {
        [$pelaku, $owned] = $this->createVerifiedPelakuFixture();
        $otherId = DB::table('umkms')->insertGetId([
            'umkm_code' => 'LSS-OTHER-10D',
            'business_name' => 'UMKM Lain',
        ]);
        $other = Umkm::query()->findOrFail($otherId);

        $this->actingAs($pelaku)
            ->post(route('pelaku-umkm.profile-change.store', $owned), [
                'business_name' => 'Tidak Boleh',
                'quality_status' => 'dipaksakan',
            ])
            ->assertSessionHasErrors('quality_status');

        $this->assertSame(0, UmkmUpdateSubmission::query()->count());

        $this->actingAs($pelaku)
            ->get(route('pelaku-umkm.profile-change.create', $other))
            ->assertNotFound();
    }

    public function test_source_owned_umkm_cannot_use_direct_official_update_service(): void
    {
        [, $umkm] = $this->createVerifiedPelakuFixture();

        if (! Schema::hasColumn('umkms', 'source_system')
            || ! Schema::hasColumn('umkms', 'source_record_id')) {
            $this->markTestSkipped('Source lifecycle columns are unavailable in this fresh schema.');
        }

        DB::table('umkms')->where('id', $umkm->id)->update([
            'source_system' => 'LSS',
            'source_record_id' => 'SRC-10D-001',
        ]);
        $umkm = $umkm->fresh();
        $before = $this->sourceSnapshot($umkm->id);

        try {
            app(UmkmOfficialService::class)->updateOfficial(
                $umkm,
                ['business_name' => 'Tidak Boleh Menimpa Sumber'],
                999
            );
            $this->fail('Source-owned direct official update must be rejected.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('Source-owned UMKM cannot be directly updated', $exception->getMessage());
        }

        $this->assertSame($before, $this->sourceSnapshot($umkm->id));
    }

    public function test_second_approval_creates_new_revision_and_preserves_first_revision(): void
    {
        [$pelaku, $umkm] = $this->createVerifiedPelakuFixture();
        $reviewer = User::query()->create([
            'name' => 'Reviewer Dua',
            'email' => 'reviewer-two@example.test',
            'password' => 'ReviewerPassword123',
            'is_active' => true,
        ]);
        $request = Request::create('/review', 'POST');
        $request->setUserResolver(fn () => $reviewer);
        $service = app(UmkmProposalService::class);

        $first = $service->createProposal([
            'umkm_id' => $umkm->id,
            'business_name' => 'Efektif Satu',
            'status_data' => 'diajukan',
        ], $pelaku->id);
        $service->reviewProposal($first, 'disetujui', null, $reviewer->id, $request, app(AuditLogger::class));
        $firstRevision = UmkmProfileOverrideRevision::query()->firstOrFail();
        $firstSnapshot = $firstRevision->toArray();

        $second = $service->createProposal([
            'umkm_id' => $umkm->id,
            'business_name' => 'Efektif Dua',
            'status_data' => 'diajukan',
        ], $pelaku->id);
        $service->reviewProposal($second, 'disetujui', null, $reviewer->id, $request, app(AuditLogger::class));

        $this->assertSame(2, UmkmProfileOverrideRevision::query()->count());
        $latest = UmkmProfileOverrideRevision::query()->latest('id')->firstOrFail();
        $this->assertSame($firstRevision->id, $latest->previous_override_revision_id);
        $this->assertEquals($firstSnapshot, $firstRevision->fresh()->toArray());

        $resolved = app(EffectiveUmkmProfileService::class)->resolve($umkm->fresh());
        $this->assertSame('Efektif Dua', $resolved['effective']['business_name']);
        $this->assertSame('Nama Sumber Tetap', $resolved['source']['business_name']);
    }

    private function createVerifiedPelakuFixture(): array
    {
        DB::table('roles')->updateOrInsert(
            ['code' => 'pelaku_umkm'],
            [
                'name' => 'Pelaku UMKM',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $roleId = (int) DB::table('roles')->where('code', 'pelaku_umkm')->value('id');

        foreach (['umkm.workspace.access', 'umkm.profile.propose'] as $code) {
            $permissionId = DB::table('permissions')->where('code', $code)->value('id');
            $this->assertNotNull($permissionId);
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $pelaku = User::query()->create([
            'name' => 'Pelaku Override',
            'email' => 'pelaku-override@example.test',
            'password' => 'PelakuOverridePassword123',
            'is_active' => true,
        ]);
        DB::table('user_roles')->insert([
            'user_id' => $pelaku->id,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $umkmId = DB::table('umkms')->insertGetId([
            'umkm_code' => 'LSS-OVERRIDE-10D',
            'business_name' => 'Nama Sumber Tetap',
        ]);
        $umkm = Umkm::query()->findOrFail($umkmId);

        if (Schema::hasTable('umkm_baseline_profiles')) {
            DB::table('umkm_baseline_profiles')->insert([
                'umkm_id' => $umkmId,
                'employee_count' => 3,
                'marketing_method_id' => null,
                'status_data' => 'terbatas',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $claimId = DB::table('umkm_account_claims')->insertGetId([
            'umkm_id' => $umkmId,
            'claim_reference' => 'CLM10D'.str_pad((string) $umkmId, 6, '0', STR_PAD_LEFT),
            'claim_type' => 'self_claim',
            'applicant_name' => $pelaku->name,
            'applicant_email' => $pelaku->email,
            'relationship_type' => 'owner',
            'status' => 'activated',
            'activated_user_id' => $pelaku->id,
            'submitted_at' => now(),
            'activation_completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('umkm_user_links')->insert([
            'umkm_id' => $umkmId,
            'user_id' => $pelaku->id,
            'relationship_type' => 'owner',
            'is_primary' => false,
            'source_claim_id' => $claimId,
            'binding_source' => 'account_claim_activation',
            'verification_status' => 'verified',
            'is_active' => true,
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$pelaku, $umkm];
    }

    private function sourceSnapshot(int $umkmId): array
    {
        return [
            'umkm' => (array) DB::table('umkms')->where('id', $umkmId)->first(),
            'baseline' => Schema::hasTable('umkm_baseline_profiles')
                ? (array) DB::table('umkm_baseline_profiles')->where('umkm_id', $umkmId)->first()
                : [],
        ];
    }
}