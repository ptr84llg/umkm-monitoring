<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmProfileOverrideRevision;
use App\Models\User;
use App\Services\PelakuUmkm\EffectiveUmkmProfileService;
use App\Services\Proposal\UmkmProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDinasProfileReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $pelaku;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'admin_dinas'],
            ['name' => 'Admin Dinas', 'is_active' => true]
        );
        $reviewPermission = Permission::query()->firstOrCreate(
            ['code' => 'umkm.profile.review'],
            ['name' => 'Review Profile', 'module' => 'umkm']
        );
        $adminRole->permissions()->syncWithoutDetaching([$reviewPermission->id]);

        $this->admin = User::query()->create([
            'name' => 'Admin Dinas Review',
            'email' => 'admin-review@example.test',
            'username' => 'admin-review',
            'password' => 'test-password',
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $pelakuRole = Role::query()->firstOrCreate(
            ['code' => 'pelaku_umkm'],
            ['name' => 'Pelaku UMKM', 'is_active' => true]
        );
        $this->pelaku = User::query()->create([
            'name' => 'Pelaku Review',
            'email' => 'pelaku-review@example.test',
            'username' => 'pelaku-review',
            'password' => 'test-password',
            'is_active' => true,
        ]);
        $this->pelaku->roles()->attach($pelakuRole->id);
    }

    public function test_admin_dinas_approval_creates_override_without_mutating_source_and_cannot_review_twice(): void
    {
        $umkm = $this->createUmkm('REV-001', 'Nama Sumber');
        $proposal = $this->createProposal($umkm, ['business_name' => 'Nama Disetujui']);
        $sourceBefore = (array) DB::table('umkms')->where('id', $umkm->id)->first();

        $this->actingAs($this->admin)
            ->get(route('admin-dinas.profile-reviews.index'))
            ->assertOk()
            ->assertSee('Nama Sumber');

        $this->actingAs($this->admin)
            ->get(route('admin-dinas.profile-reviews.show', $proposal))
            ->assertOk()
            ->assertSee('Nama Disetujui');

        $this->actingAs($this->admin)
            ->post(route('admin-dinas.profile-reviews.review', $proposal), [
                'decision' => 'disetujui',
                'review_note' => 'Data pendukung sesuai.',
            ])
            ->assertRedirect(route('admin-dinas.profile-reviews.show', $proposal));

        $this->assertDatabaseHas('umkm_update_submissions', [
            'id' => $proposal->id,
            'status_data' => 'disetujui',
            'reviewed_by' => $this->admin->id,
        ]);
        $this->assertDatabaseCount('data_validation_reviews', 1);
        $this->assertDatabaseCount('umkm_profile_override_revisions', 1);
        $this->assertDatabaseCount('umkm_current_profile_overrides', 1);

        $effective = app(EffectiveUmkmProfileService::class)->resolve($umkm->fresh());
        $this->assertSame('Nama Disetujui', $effective['effective']['business_name']);
        $this->assertSame($sourceBefore, (array) DB::table('umkms')->where('id', $umkm->id)->first());

        $this->actingAs($this->admin)
            ->from(route('admin-dinas.profile-reviews.show', $proposal))
            ->post(route('admin-dinas.profile-reviews.review', $proposal), [
                'decision' => 'ditolak',
                'review_note' => 'Tidak boleh review kedua.',
            ])
            ->assertSessionHasErrors('decision');

        $this->assertDatabaseCount('data_validation_reviews', 1);
        $this->assertDatabaseCount('umkm_profile_override_revisions', 1);
    }

    public function test_reject_and_revision_request_append_review_without_creating_override(): void
    {
        foreach (['ditolak', 'perlu_perbaikan'] as $index => $decision) {
            $umkm = $this->createUmkm('REV-REJ-00'.($index + 1), 'Nama Sumber '.($index + 1));
            $proposal = $this->createProposal($umkm, ['business_name' => 'Nama Usulan '.($index + 1)]);
            $sourceBefore = (array) DB::table('umkms')->where('id', $umkm->id)->first();

            $this->actingAs($this->admin)
                ->post(route('admin-dinas.profile-reviews.review', $proposal), [
                    'decision' => $decision,
                    'review_note' => 'Catatan wajib '.$decision,
                ])
                ->assertRedirect(route('admin-dinas.profile-reviews.show', $proposal));

            $this->assertDatabaseHas('umkm_update_submissions', [
                'id' => $proposal->id,
                'status_data' => $decision,
            ]);
            $this->assertDatabaseHas('data_validation_reviews', [
                'submission_id' => $proposal->id,
                'decision' => $decision,
            ]);
            $this->assertSame($sourceBefore, (array) DB::table('umkms')->where('id', $umkm->id)->first());
        }

        $this->assertDatabaseCount('umkm_profile_override_revisions', 0);
        $this->assertDatabaseCount('umkm_current_profile_overrides', 0);
    }

    public function test_sequential_partial_approvals_keep_previous_override_in_cumulative_overlay(): void
    {
        $umkm = $this->createUmkm('REV-CUM-001', 'Nama Sumber');
        $sourceBefore = (array) DB::table('umkms')->where('id', $umkm->id)->first();

        $first = $this->createProposal($umkm, ['business_name' => 'Nama Override']);
        $this->approve($first);

        $second = $this->createProposal($umkm->fresh(), ['established_date' => '2020-05-17']);
        $this->approve($second);

        $effective = app(EffectiveUmkmProfileService::class)->resolve($umkm->fresh());
        $this->assertSame('Nama Override', $effective['effective']['business_name']);
        $this->assertSame('2020-05-17', $effective['effective']['established_date']);

        $revisions = UmkmProfileOverrideRevision::query()->orderBy('id')->get();
        $this->assertCount(2, $revisions);
        $this->assertSame($revisions[0]->id, $revisions[1]->previous_override_revision_id);
        $this->assertSame('Nama Override', $revisions[1]->override_data['business_name']);
        $this->assertSame('2020-05-17', $revisions[1]->override_data['established_date']);
        $this->assertSame($sourceBefore, (array) DB::table('umkms')->where('id', $umkm->id)->first());
    }

    public function test_stale_same_field_approval_is_rejected_and_transaction_rolls_back(): void
    {
        $umkm = $this->createUmkm('REV-STALE-001', 'Nama Awal');
        $first = $this->createProposal($umkm, ['business_name' => 'Nama Pertama']);
        $stale = $this->createProposal($umkm, ['business_name' => 'Nama Kedua']);

        $this->approve($first);

        $this->actingAs($this->admin)
            ->from(route('admin-dinas.profile-reviews.show', $stale))
            ->post(route('admin-dinas.profile-reviews.review', $stale), [
                'decision' => 'disetujui',
                'review_note' => 'Approval stale harus diblokir.',
            ])
            ->assertSessionHasErrors('decision');

        $this->assertDatabaseHas('umkm_update_submissions', [
            'id' => $stale->id,
            'status_data' => 'diajukan',
        ]);
        $this->assertDatabaseMissing('data_validation_reviews', [
            'submission_id' => $stale->id,
        ]);
        $this->assertDatabaseCount('umkm_profile_override_revisions', 1);

        $effective = app(EffectiveUmkmProfileService::class)->resolve($umkm->fresh());
        $this->assertSame('Nama Pertama', $effective['effective']['business_name']);
    }

    private function createUmkm(string $code, string $name): Umkm
    {
        return Umkm::query()->create([
            'umkm_code' => $code,
            'business_name' => $name,
        ]);
    }

    private function createProposal(Umkm $umkm, array $changes)
    {
        return app(UmkmProposalService::class)->createProposal(
            array_merge(['umkm_id' => $umkm->id, 'status_data' => 'diajukan'], $changes),
            $this->pelaku->id
        );
    }

    private function approve($proposal): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin-dinas.profile-reviews.review', $proposal), [
                'decision' => 'disetujui',
                'review_note' => 'Disetujui untuk pengujian.',
            ])
            ->assertRedirect(route('admin-dinas.profile-reviews.show', $proposal));
    }
}