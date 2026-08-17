<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDinasDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dinas_with_financial_permission_sees_internal_financial_summary(): void
    {
        $user = $this->createAdminDinasUser(
            'dinas-financial@example.test',
            true
        );

        $this->actingAs($user)
            ->get('/admin-dinas/dashboard')
            ->assertOk()
            ->assertSee('Monitoring UMKM Admin Dinas')
            ->assertSee('Cakupan Keuangan')
            ->assertSee('Modal terdata')
            ->assertSee('0 berbeda dari belum tersedia')
            ->assertSee('Buka Ekonomi & Keuangan', false)
            ->assertDontSee('Detail Nilai Keuangan Terdata')
            ->assertDontSee('Sumber Pinjaman yang Terdata');
    }

    public function test_admin_dinas_without_financial_permission_sees_restricted_financial_card_only(): void
    {
        $user = $this->createAdminDinasUser(
            'dinas-read-only@example.test',
            false
        );

        $this->actingAs($user)
            ->get('/admin-dinas/dashboard')
            ->assertOk()
            ->assertSee('Monitoring UMKM Admin Dinas')
            ->assertSee('Akses memerlukan izin data keuangan sensitif')
            ->assertDontSee('Modal terdata')
            ->assertDontSee('Buka Ekonomi & Keuangan', false)
            ->assertDontSee('Detail Nilai Keuangan Terdata');
    }

    private function createAdminDinasUser(string $email, bool $withFinancial): User
    {
        $user = User::query()->create([
            'name' => 'Admin Dinas Test',
            'email' => $email,
            'password' => 'test-password',
            'is_active' => true,
        ]);

        $role = Role::query()->firstOrCreate(
            ['code' => 'admin_dinas'],
            [
                'name' => 'Admin Dinas',
                'is_active' => true,
            ]
        );

        $readPermission = Permission::query()->firstOrCreate(
            ['code' => 'umkm.read.official'],
            [
                'name' => 'Read Official UMKM',
                'module' => 'umkm',
            ]
        );

        $role->permissions()->syncWithoutDetaching([$readPermission->id]);

        if ($withFinancial) {
            $financialPermission = Permission::query()
                ->where('code', 'umkm.sensitive.financial')
                ->firstOrFail();

            $role->permissions()->syncWithoutDetaching([$financialPermission->id]);
        }
        else {
            $financialPermissionId = Permission::query()
                ->where('code', 'umkm.sensitive.financial')
                ->value('id');

            if ($financialPermissionId) {
                $role->permissions()->detach($financialPermissionId);
            }
        }

        $user->roles()->attach($role->id);

        return $user;
    }
}
