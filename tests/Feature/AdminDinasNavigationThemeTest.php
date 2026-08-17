<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDinasNavigationThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dinas_menu_exposes_direct_spatial_and_financial_navigation(): void
    {
        $user = $this->createAdminDinasUser();

        $response = $this->actingAs($user)
            ->get('/admin-dinas/dashboard')
            ->assertOk()
            ->assertSee('Peta Wilayah')
            ->assertSee('Ekonomi & Keuangan')
            ->assertSee(route('admin-dinas.analytics.spatial'), false)
            ->assertSee(route('admin-dinas.analytics.financial'), false);

        $html = $response->getContent();

        $this->assertStringContainsString(
            'data-menu-title="Peta Wilayah"',
            $html
        );

        $this->assertStringContainsString(
            'data-menu-title="Ekonomi &amp; Keuangan"',
            $html
        );
    }

    public function test_internal_topbar_uses_solid_theme_color_contract(): void
    {
        $css = file_get_contents(
            public_path('assets/css/core/layout/umkm-internal-shell.css')
        );

        $this->assertIsString($css);
        $this->assertStringContainsString(
            'background: var(--umkm-primary);',
            $css
        );
        $this->assertStringContainsString(
            '.dashboard-topbar .dashboard-mega-trigger',
            $css
        );
        $this->assertStringContainsString(
            '.dashboard-topbar .dashboard-topbar-link',
            $css
        );
        $this->assertStringContainsString(
            'color: #fff;',
            $css
        );
    }

    public function test_coordinate_permission_definition_exists_after_migrations(): void
    {
        $this->assertDatabaseHas('permissions', [
            'code' => 'umkm.sensitive.coordinate',
            'module' => 'umkm',
        ]);
    }

    private function createAdminDinasUser(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Dinas Navigation Test',
            'email' => 'admin-dinas-navigation@example.test',
            'username' => 'admin-dinas-navigation',
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

        $permissionIds = collect([
            [
                'code' => 'umkm.read.official',
                'name' => 'Read Official UMKM',
            ],
            [
                'code' => 'umkm.sensitive.financial',
                'name' => 'View Sensitive Financial',
            ],
            [
                'code' => 'umkm.sensitive.coordinate',
                'name' => 'View Sensitive Coordinate',
            ],
        ])->map(function (array $permission): int {
            return Permission::query()->firstOrCreate(
                ['code' => $permission['code']],
                [
                    'name' => $permission['name'],
                    'module' => 'umkm',
                ]
            )->id;
        })->all();

        $role->permissions()->syncWithoutDetaching($permissionIds);
        $user->roles()->attach($role->id);

        return $user;
    }
}
