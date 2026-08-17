<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Umkm\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDinasInternalWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_workspace_routes_are_get_only_and_registered(): void
    {
        $legacyReadOnlyRoutes = [
            'admin-dinas.dashboard',
            'admin-dinas.umkm.index',
            'admin-dinas.umkm.show',
            'admin-dinas.analytics.index',
            'admin-dinas.analytics.spatial',
            'admin-dinas.analytics.financial',
        ];

        foreach ($legacyReadOnlyRoutes as $routeName) {
            $this->assertTrue(Route::has($routeName), $routeName . ' harus aktif.');

            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertEmpty(
                array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']),
                $routeName . ' harus tetap read-only.'
            );
        }

        $allowedWriteRoutes = [
            'admin-dinas.account-claims.invite.store',
            'admin-dinas.account-claims.review',
            'admin-dinas.account-claims.resend',
            'admin-dinas.profile-reviews.review',
        ];

        foreach ($allowedWriteRoutes as $routeName) {
            $this->assertTrue(Route::has($routeName), $routeName . ' harus aktif.');

            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertContains('POST', $route->methods());
            $this->assertEmpty(array_intersect($route->methods(), ['PUT', 'PATCH', 'DELETE']));
        }

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'admin-dinas')) {
                continue;
            }

            $writeMethods = array_values(array_intersect(
                $route->methods(),
                ['POST', 'PUT', 'PATCH', 'DELETE']
            ));

            if ($writeMethods === []) {
                continue;
            }

            $routeName = (string) $route->getName();
            $this->assertContains(
                $routeName,
                $allowedWriteRoutes,
                'Checkpoint 10E hanya mengizinkan write route Admin Dinas untuk account claim dan immutable profile review.'
            );
            $this->assertSame(['POST'], $writeMethods);
        }
    }

    public function test_admin_dinas_can_open_data_detail_and_analytics_read_only(): void
    {
        $user = $this->createAdminDinasUser('workspace-read@example.test', false);

        $umkm = Umkm::query()->create([
            'umkm_code' => 'TEST-READ-001',
            'business_name' => 'UMKM UJI READ ONLY',
            'status_data' => 'resmi',
            'quality_status' => 'lengkap_terpetakan',
        ]);

        $this->actingAs($user)->get('/admin-dinas/umkm')
            ->assertOk()->assertSee('Data UMKM Internal')->assertSee('UMKM UJI READ ONLY');

        $this->actingAs($user)->get('/admin-dinas/umkm/' . $umkm->id)
            ->assertOk()->assertSee('Detail Read-only')->assertSee('UMKM UJI READ ONLY')->assertDontSee('Edit');

        $this->actingAs($user)->get('/admin-dinas/analytics')
            ->assertOk()->assertSee('Analitik UMKM Admin Dinas')->assertSee('Profil Sektor')->assertSee('Mutu Data');
    }

    public function test_financial_analytics_preserves_and_separates_quality_marker_source_values(): void
    {
        $user = $this->createAdminDinasUser('workspace-financial@example.test', true);

        $identified = Umkm::query()->create([
            'umkm_code' => 'TEST-FIN-001',
            'business_name' => 'UMKM SUMBER TERIDENTIFIKASI',
            'status_data' => 'resmi',
            'quality_status' => 'lengkap_terpetakan',
        ]);

        $issue = Umkm::query()->create([
            'umkm_code' => 'TEST-FIN-002',
            'business_name' => 'UMKM SUMBER QUALITY ISSUE',
            'status_data' => 'resmi',
            'quality_status' => 'lengkap_terpetakan',
        ]);

        $this->insertBaseline($identified->id, 'Mekaar');
        $this->insertBaseline($issue->id, 'Mekaar Data keuangan tidak tersedia');

        $this->actingAs($user)->get('/admin-dinas/analytics/financial')
            ->assertOk()
            ->assertSee('Sumber Pinjaman Teridentifikasi')
            ->assertSee('Catatan Mutu Sumber Pinjaman')
            ->assertSee('Mekaar')
            ->assertSee('Mekaar Data keuangan tidak tersedia');
    }

    public function test_financial_analytics_requires_sensitive_financial_permission(): void
    {
        $user = $this->createAdminDinasUser('workspace-no-financial@example.test', false);

        $this->actingAs($user)->get('/admin-dinas/analytics/financial')->assertForbidden();
    }

    private function createAdminDinasUser(string $email, bool $withFinancial): User
    {
        $user = User::query()->create([
            'name' => 'Admin Dinas Test',
            'email' => $email,
            'username' => str_replace(['@', '.'], '-', $email),
            'password' => 'test-password',
            'is_active' => true,
        ]);

        $role = Role::query()->firstOrCreate(
            ['code' => 'admin_dinas'],
            ['name' => 'Admin Dinas', 'is_active' => true]
        );

        $read = Permission::query()->firstOrCreate(
            ['code' => 'umkm.read.official'],
            ['name' => 'Read Official UMKM', 'module' => 'umkm']
        );

        $role->permissions()->syncWithoutDetaching([$read->id]);

        if ($withFinancial) {
            $financial = Permission::query()->firstOrCreate(
                ['code' => 'umkm.sensitive.financial'],
                ['name' => 'View Sensitive Financial', 'module' => 'umkm']
            );
            $role->permissions()->syncWithoutDetaching([$financial->id]);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function insertBaseline(int $umkmId, string $loanSource): void
    {
        $row = ['umkm_id' => $umkmId];

        if (Schema::hasColumn('umkm_baseline_profiles', 'loan_source')) {
            $row['loan_source'] = $loanSource;
        }
        if (Schema::hasColumn('umkm_baseline_profiles', 'status_data')) {
            $row['status_data'] = 'terbatas';
        }
        if (Schema::hasColumn('umkm_baseline_profiles', 'created_at')) {
            $row['created_at'] = now();
        }
        if (Schema::hasColumn('umkm_baseline_profiles', 'updated_at')) {
            $row['updated_at'] = now();
        }

        DB::table('umkm_baseline_profiles')->insert($row);
    }
}
