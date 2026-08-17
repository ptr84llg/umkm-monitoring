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

        $this->assertStringContainsString('data-menu-title="Peta Wilayah"', $html);
        $this->assertStringContainsString('data-menu-title="Ekonomi &amp; Keuangan"', $html);
    }

    public function test_internal_topbar_uses_core_bootstrap_theme_contract(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/dashboard.blade.php'));
        $css = file_get_contents(public_path('assets/css/core/layout/umkm-internal-shell.css'));
        $bridge = file_get_contents(public_path('assets/css/core/foundation/umkm-bootstrap-bridge.css'));

        $this->assertIsString($layout);
        $this->assertIsString($css);
        $this->assertIsString($bridge);

        $this->assertStringContainsString('class="dashboard-topbar bg-primary"', $layout);
        $this->assertStringContainsString('<strong class="text-white">Ruang Kerja</strong>', $layout);
        $this->assertStringContainsString('<small class="text-white-50">Monitoring UMKM</small>', $layout);
        $this->assertStringContainsString('--bs-primary-rgb: var(--umkm-primary-rgb);', $bridge);

        $this->assertStringNotContainsString('/* INTERNALLAYOUT-UI3: scroll-aware topbar state */', $css);
        $this->assertStringNotContainsString('data-scroll-state="scrolling"', $css);
        $this->assertStringNotContainsString('opacity: .78;', $css);
        $this->assertStringNotContainsString('backdrop-filter: blur(16px) saturate(1.05);', $css);
        $this->assertStringNotContainsString('rgba(255, 255, 255, .92) 46%', $css);
    }

    public function test_dashboard_logo_container_uses_existing_internal_core_theme(): void
    {
        $shell = file_get_contents(public_path('assets/css/core/layout/umkm-internal-shell.css'));
        $ui = file_get_contents(public_path('assets/css/core/foundation/umkm-ui.css'));

        $this->assertIsString($shell);
        $this->assertIsString($ui);

        $this->assertSame(
            1,
            preg_match(
                '/\.system-brand-mark,\s*\.dashboard-brand-mark\.system-brand-mark\s*\{/s',
                $ui
            )
        );

        $this->assertSame(
            0,
            preg_match(
                '/\.dashboard-brand-mark\.system-brand-mark\s*\{\s*width:\s*2\.75rem;[^}]*background:/s',
                $ui
            )
        );
        $this->assertStringContainsString('.dashboard-brand-mark,', $shell);
        $this->assertStringContainsString(
            'linear-gradient(135deg, var(--umkm-primary), var(--umkm-primary-dark));',
            $shell
        );

        $this->assertStringContainsString('.landing-brand-mark.system-brand-mark,', $ui);
        $this->assertStringContainsString('.auth-brand-mark.system-brand-mark', $ui);
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
            ['name' => 'Admin Dinas', 'is_active' => true]
        );

        $permissionIds = collect([
            ['code' => 'umkm.read.official', 'name' => 'Read Official UMKM'],
            ['code' => 'umkm.sensitive.financial', 'name' => 'View Sensitive Financial'],
            ['code' => 'umkm.sensitive.coordinate', 'name' => 'View Sensitive Coordinate'],
        ])->map(function (array $permission): int {
            return Permission::query()->firstOrCreate(
                ['code' => $permission['code']],
                ['name' => $permission['name'], 'module' => 'umkm']
            )->id;
        })->all();

        $role->permissions()->syncWithoutDetaching($permissionIds);
        $user->roles()->attach($role->id);

        return $user;
    }
}
