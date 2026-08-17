<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Umkm\Umkm;
use App\Models\User;
use App\Services\AdminDinas\AdminDinasDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDinasContextHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_pages_show_active_context(): void
    {
        $user = $this->createAdminDinasUser();

        foreach ([
            '/admin-dinas/dashboard',
            '/admin-dinas/umkm',
            '/admin-dinas/analytics',
            '/admin-dinas/analytics/spatial',
            '/admin-dinas/analytics/financial',
        ] as $uri) {
            $this->actingAs($user)
                ->get($uri)
                ->assertOk()
                ->assertSee('Konteks Aktif')
                ->assertSee('Seluruh Kota Lubuk Linggau');
        }
    }

    public function test_controller_accepts_village_and_quality_filters_for_admin_dinas_analytics(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/AdminDinas/AdminDinasController.php')
        );

        $this->assertIsString($controller);
        $this->assertStringContainsString(
            "'village_id' => ['nullable', 'integer', 'min:1']",
            $controller
        );
        $this->assertStringContainsString(
            "'quality_status' => ['nullable', 'string', 'max:80']",
            $controller
        );
    }

    public function test_dashboard_quality_filter_is_real_backend_filter(): void
    {
        Umkm::query()->create([
            'umkm_code' => 'CTX-001',
            'business_name' => 'CTX LENGKAP',
            'status_data' => 'resmi',
            'quality_status' => 'lengkap_terpetakan',
        ]);

        Umkm::query()->create([
            'umkm_code' => 'CTX-002',
            'business_name' => 'CTX BELUM',
            'status_data' => 'resmi',
            'quality_status' => 'administratif_belum_terpetakan',
        ]);

        $data = app(AdminDinasDashboardService::class)->build([
            'quality_status' => 'lengkap_terpetakan',
        ], false);

        $this->assertSame(1, $data['summary']['total_umkm']);
        $this->assertContains(
            'lengkap_terpetakan',
            collect($data['filter_options']['qualityStatuses'] ?? [])->all()
        );
    }

    public function test_village_is_primary_filter_and_advanced_filter_is_removed(): void
    {
        $contracts = [
            'pages/admin-dinas/dashboard.blade.php' => "['village_id', 'Kelurahan'",
            'pages/admin-dinas/umkm-index.blade.php' => 'name="village_id"',
            'pages/admin-dinas/analytics/index.blade.php' => "['village_id', 'Kelurahan'",
            'pages/admin-dinas/analytics/financial.blade.php' => "['village_id', 'Kelurahan'",
            'pages/admin-dinas/analytics/spatial.blade.php' => 'name="village_id"',
        ];

        foreach ($contracts as $relative => $marker) {
            $source = file_get_contents(resource_path('views/' . $relative));
            $this->assertIsString($source);
            $this->assertStringContainsString($marker, $source);
            $this->assertStringContainsString('Kelurahan', $source);
        }

        $dataView = file_get_contents(resource_path('views/pages/admin-dinas/umkm-index.blade.php'));
        $this->assertStringNotContainsString('Filter Lanjutan', $dataView);
    }

    public function test_cards_use_visible_core_borders(): void
    {
        foreach ([
            'pages/admin-dinas/dashboard.blade.php',
            'pages/admin-dinas/umkm-index.blade.php',
            'pages/admin-dinas/umkm-show.blade.php',
            'pages/admin-dinas/analytics/index.blade.php',
            'pages/admin-dinas/analytics/financial.blade.php',
            'pages/admin-dinas/analytics/spatial.blade.php',
        ] as $relative) {
            $source = file_get_contents(resource_path('views/' . $relative));
            $this->assertIsString($source);
            $this->assertStringContainsString('card border shadow-sm', $source);
            $this->assertStringNotContainsString('card border-0 shadow-sm', $source);
        }
    }

    public function test_data_umkm_preserves_context_in_module_navigation(): void
    {
        $user = $this->createAdminDinasUser();

        $this->actingAs($user)
            ->get('/admin-dinas/umkm?quality_status=lengkap_terpetakan')
            ->assertOk()
            ->assertSee('Mutu: Lengkap Terpetakan')
            ->assertSee('quality_status=lengkap_terpetakan', false);
    }

    private function createAdminDinasUser(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Dinas Context Test',
            'email' => 'admin-dinas-context@example.test',
            'username' => 'admin-dinas-context',
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
