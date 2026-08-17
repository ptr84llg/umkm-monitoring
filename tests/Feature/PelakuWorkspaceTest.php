<?php

namespace Tests\Feature;

use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmAccountClaim;
use App\Models\Umkm\UmkmUserLink;
use App\Models\User;
use App\Policies\Umkm\UmkmPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PelakuWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private int $roleId;
    private int $permissionId;

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

        $this->roleId = (int) DB::table('roles')
            ->where('code', 'pelaku_umkm')
            ->value('id');

        $this->permissionId = (int) DB::table('permissions')
            ->where('code', 'umkm.workspace.access')
            ->value('id');

        $this->assertGreaterThan(0, $this->permissionId);

        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $this->roleId,
                'permission_id' => $this->permissionId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('umkms')->insert([
            ['umkm_code' => 'LSS-WORK-001', 'business_name' => 'Usaha Workspace Satu'],
            ['umkm_code' => 'LSS-WORK-002', 'business_name' => 'Usaha Workspace Dua'],
            ['umkm_code' => 'LSS-WORK-003', 'business_name' => 'Usaha Bukan Milik'],
        ]);
    }

    public function test_active_verified_binding_opens_read_only_workspace(): void
    {
        $user = $this->createPelaku('workspace@example.test');
        $first = Umkm::query()->where('umkm_code', 'LSS-WORK-001')->firstOrFail();
        $second = Umkm::query()->where('umkm_code', 'LSS-WORK-002')->firstOrFail();

        $this->createVerifiedBinding($user, $first, 'WS-CLAIM-001');
        $this->createVerifiedBinding($user, $second, 'WS-CLAIM-002');

        $sourceBefore = DB::table('umkms')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        $this->actingAs($user)
            ->get(route('pelaku-umkm.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Pelaku UMKM')
            ->assertSee('Usaha Workspace Satu')
            ->assertSee('Usaha Workspace Dua');

        $this->actingAs($user)
            ->get(route('pelaku-umkm.umkm.index'))
            ->assertOk()
            ->assertSee('LSS-WORK-001')
            ->assertSee('LSS-WORK-002')
            ->assertDontSee('LSS-WORK-003');

        $this->actingAs($user)
            ->get(route('pelaku-umkm.umkm.show', $first))
            ->assertOk()
            ->assertSee('Usaha Workspace Satu');

        $sourceAfter = DB::table('umkms')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $this->assertEquals($sourceBefore, $sourceAfter);
    }

    public function test_role_and_permission_without_verified_binding_cannot_open_workspace(): void
    {
        $user = $this->createPelaku('no-binding@example.test');

        $this->actingAs($user)
            ->get(route('pelaku-umkm.dashboard'))
            ->assertForbidden();
    }

    public function test_inactive_or_revoked_binding_cannot_open_workspace(): void
    {
        $user = $this->createPelaku('revoked@example.test');
        $umkm = Umkm::query()->where('umkm_code', 'LSS-WORK-001')->firstOrFail();
        $link = $this->createVerifiedBinding($user, $umkm, 'WS-CLAIM-REV');

        $link->update([
            'is_active' => false,
            'verification_status' => UmkmUserLink::VERIFICATION_REVOKED,
            'revoked_at' => now(),
            'revocation_reason' => 'Fixture revocation',
        ]);

        $this->actingAs($user)
            ->get(route('pelaku-umkm.dashboard'))
            ->assertForbidden();
    }

    public function test_verified_pelaku_cannot_view_another_umkm(): void
    {
        $user = $this->createPelaku('isolation@example.test');
        $owned = Umkm::query()->where('umkm_code', 'LSS-WORK-001')->firstOrFail();
        $other = Umkm::query()->where('umkm_code', 'LSS-WORK-003')->firstOrFail();

        $this->createVerifiedBinding($user, $owned, 'WS-CLAIM-ISO');

        $this->actingAs($user)
            ->get(route('pelaku-umkm.umkm.show', $other))
            ->assertNotFound();
    }

    public function test_pelaku_policy_is_read_only_until_profile_override_checkpoint(): void
    {
        $user = $this->createPelaku('policy@example.test');
        $owned = Umkm::query()->where('umkm_code', 'LSS-WORK-001')->firstOrFail();
        $other = Umkm::query()->where('umkm_code', 'LSS-WORK-003')->firstOrFail();
        $this->createVerifiedBinding($user, $owned, 'WS-CLAIM-POL');

        $policy = new UmkmPolicy();

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $owned));
        $this->assertFalse($policy->view($user, $other));
        $this->assertFalse($policy->update($user, $owned));
    }

    private function createPelaku(string $email): User
    {
        $user = User::query()->create([
            'name' => 'Pelaku Workspace',
            'email' => $email,
            'password' => Hash::make('PasswordWorkspace123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $this->roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->fresh();
    }

    private function createVerifiedBinding(User $user, Umkm $umkm, string $reference): UmkmUserLink
    {
        $reviewer = User::query()->create([
            'name' => 'Reviewer '.$reference,
            'email' => strtolower($reference).'@example.test',
            'password' => Hash::make('ReviewerPassword123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $claim = UmkmAccountClaim::query()->create([
            'umkm_id' => $umkm->id,
            'claim_reference' => $reference,
            'claim_type' => UmkmAccountClaim::TYPE_SELF_CLAIM,
            'applicant_name' => $user->name,
            'applicant_email' => $user->email,
            'relationship_type' => 'owner',
            'status' => UmkmAccountClaim::STATUS_ACTIVATED,
            'activated_user_id' => $user->id,
            'reviewed_by_user_id' => $reviewer->id,
            'submitted_at' => now(),
            'reviewed_at' => now(),
            'approved_at' => now(),
            'activation_completed_at' => now(),
        ]);

        return UmkmUserLink::query()->create([
            'umkm_id' => $umkm->id,
            'user_id' => $user->id,
            'relationship_type' => 'owner',
            'is_primary' => false,
            'source_claim_id' => $claim->id,
            'binding_source' => UmkmUserLink::BINDING_SOURCE_ACCOUNT_CLAIM_ACTIVATION,
            'verification_status' => UmkmUserLink::VERIFICATION_VERIFIED,
            'is_active' => true,
            'verified_at' => now(),
            'verified_by_user_id' => $reviewer->id,
        ]);
    }
}