<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Umkm\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDinasRegionCascadePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_dinas_region_filters_load_shared_cascade_contract(): void
    {
        foreach ([
            'pages/admin-dinas/dashboard.blade.php',
            'pages/admin-dinas/umkm-index.blade.php',
            'pages/admin-dinas/analytics/index.blade.php',
            'pages/admin-dinas/analytics/financial.blade.php',
            'pages/admin-dinas/analytics/spatial.blade.php',
        ] as $relative) {
            $source = file_get_contents(resource_path('views/' . $relative));

            $this->assertIsString($source);
            $this->assertStringContainsString('data-region-code=', $source);
            $this->assertStringContainsString('data-parent-code=', $source);
            $this->assertStringContainsString('admin-dinas-region-cascade.js', $source);
        }

        $javascript = file_get_contents(
            public_path('assets/js/pages/admin-dinas/admin-dinas-region-cascade.js')
        );

        $this->assertIsString($javascript);
        $this->assertStringContainsString('select[name="district_id"]', $javascript);
        $this->assertStringContainsString('select[name="village_id"]', $javascript);
        $this->assertStringContainsString("districtSelect.addEventListener('change'", $javascript);
        $this->assertStringContainsString('villageSelect.disabled = true', $javascript);
        $this->assertStringContainsString('villageParentCode(option) === selectedDistrictCode', $javascript);
    }

    public function test_data_umkm_keeps_server_side_pagination_for_large_record_lists(): void
    {
        $user = $this->createAdminDinasUser('paging-data@example.test', true);

        $this->seedFinancialUmkms(30, 'DATA PAGING');

        $this->actingAs($user)
            ->get('/admin-dinas/umkm?per_page=25&page=2')
            ->assertOk()
            ->assertSee('DATA PAGING 26')
            ->assertDontSee('DATA PAGING 01')
            ->assertSee('Halaman 2 dari 2')
            ->assertSee('Sebelumnya');
    }

    public function test_financial_record_list_uses_selectable_server_side_page_size(): void
    {
        $user = $this->createAdminDinasUser('paging-financial@example.test', true);

        $this->seedFinancialUmkms(60, 'FIN PAGING');

        $response = $this->actingAs($user)
            ->get('/admin-dinas/analytics/financial?quality_status=lengkap_terpetakan&per_page=50&financial_page=2')
            ->assertOk()
            ->assertSee('Rincian Nilai Keuangan yang Tercatat')
            ->assertSee('FIN PAGING 51')
            ->assertDontSee('FIN PAGING 01')
            ->assertSee('quality_status=lengkap_terpetakan', false)
            ->assertSee('per_page=50', false)
            ->assertSee('financial_page=1', false);

        $normalizedText = preg_replace(
            '/\s+/u',
            ' ',
            html_entity_decode(strip_tags($response->getContent()), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );

        $this->assertIsString($normalizedText);
        $this->assertStringContainsString('Menampilkan 51–60 dari 60 data', trim($normalizedText));
        $this->assertStringContainsString('Halaman 2 dari 2', trim($normalizedText));

        $viewSource = file_get_contents(
            resource_path('views/pages/admin-dinas/analytics/financial.blade.php')
        );

        $this->assertIsString($viewSource);
        $this->assertStringContainsString('name="per_page"', $viewSource);
        $this->assertStringContainsString('@foreach([25, 50, 100] as $pageSize)', $viewSource);
    }

    private function seedFinancialUmkms(int $count, string $prefix): void
    {
        for ($index = 1; $index <= $count; $index++) {
            $number = str_pad((string) $index, 2, '0', STR_PAD_LEFT);

            $umkm = Umkm::query()->create([
                'umkm_code' => $prefix . '-' . $number,
                'business_name' => $prefix . ' ' . $number,
                'status_data' => 'resmi',
                'quality_status' => 'lengkap_terpetakan',
            ]);

            $row = [
                'umkm_id' => $umkm->id,
            ];

            if (Schema::hasColumn('umkm_baseline_profiles', 'capital_amount')) {
                $row['capital_amount'] = $index;
            }

            if (Schema::hasColumn('umkm_baseline_profiles', 'annual_sales_amount')) {
                $row['annual_sales_amount'] = $index * 2;
            }

            if (Schema::hasColumn('umkm_baseline_profiles', 'loan_amount')) {
                $row['loan_amount'] = $index * 3;
            }

            if (Schema::hasColumn('umkm_baseline_profiles', 'loan_source')) {
                $row['loan_source'] = 'KUR';
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

    private function createAdminDinasUser(string $email, bool $withFinancial): User
    {
        $user = User::query()->create([
            'name' => 'Admin Dinas Paging Test',
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
}