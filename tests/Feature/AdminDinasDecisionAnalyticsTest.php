<?php

namespace Tests\Feature;

use App\Models\Reference\BusinessCategoryReference;
use App\Models\Reference\BusinessTypeReference;
use App\Models\Reference\Region;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmBusinessClassification;
use App\Models\Umkm\UmkmLocation;
use App\Models\User;
use App\Services\AdminDinas\AdminDinasDecisionAnalyticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDinasDecisionAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Region $province;
    private Region $city;
    private BusinessCategoryReference $category;
    private array $districts = [];
    private array $villagesByDistrictId = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->replaceLocationTableWithProductionAlignedTestSchema();
        $this->createRegionHierarchy();

        $this->category = BusinessCategoryReference::query()->create([
            'name' => 'Kategori Decision Fixture',
            'slug' => 'kategori-decision-fixture',
            'is_active' => true,
        ]);

        $this->assertTrue(Schema::hasColumn(
            'umkm_baseline_profiles',
            'baseline_monthly_revenue'
        ));
        $this->assertTrue(Schema::hasColumn(
            'umkm_baseline_profiles',
            'annual_sales_amount'
        ));
    }

    public function test_admin_dinas_receives_competition_potential_and_micro_spatial_decision_support_without_mutating_source(): void
    {
        $admin = $this->createAdminDinas(
            'decision-admin@example.test',
            true,
            true
        );

        $typeA = $this->createType('Kuliner Decision');
        $typeB = $this->createType('Jasa Kreatif Decision');
        $typeC = $this->createType('Reparasi Decision');
        $typeD = $this->createType('Kerajinan Aktivitas Rendah');

        foreach (range(1, 6) as $index) {
            $this->createBusiness(
                'DEC-A-D1-' . $index,
                'Kuliner D1 ' . $index,
                $typeA,
                $this->districts[0],
                10000000,
                120000000,
                10000000,
                3,
                -3.2900 + ($index * 0.0002),
                102.8600 + ($index * 0.0002)
            );
        }

        foreach (range(1, 3) as $index) {
            $this->createBusiness(
                'DEC-A-D2-' . $index,
                'Kuliner D2 ' . $index,
                $typeA,
                $this->districts[1],
                45000000,
                300000000,
                20000000,
                5,
                -3.3000 + ($index * 0.0003),
                102.8700 + ($index * 0.0003)
            );
        }

        foreach (range(1, 5) as $index) {
            $this->createBusiness(
                'DEC-A-D3-' . $index,
                'Kuliner D3 ' . $index,
                $typeA,
                $this->districts[2],
                40000000,
                220000000,
                15000000,
                4,
                -3.3100 + ($index * 0.0005),
                102.8800 + ($index * 0.0005)
            );
        }

        foreach (range(1, 7) as $index) {
            $this->createBusiness(
                'DEC-A-D4-' . $index,
                'Kuliner D4 ' . $index,
                $typeA,
                $this->districts[3],
                25000000,
                100000000,
                8000000,
                2,
                -3.3200 + ($index * 0.0006),
                102.8900 + ($index * 0.0006)
            );
        }

        foreach (range(1, 6) as $index) {
            $this->createBusiness(
                'DEC-B-D2-' . $index,
                'Jasa Kreatif D2 ' . $index,
                $typeB,
                $this->districts[1],
                20000000,
                90000000,
                10000000,
                2,
                -3.3050 + ($index * 0.0004),
                102.8750 + ($index * 0.0004)
            );
        }

        foreach (range(1, 3) as $index) {
            $this->createBusiness(
                'DEC-C-D2-' . $index,
                'Reparasi D2 ' . $index,
                $typeC,
                $this->districts[1],
                35000000,
                190000000,
                18000000,
                3,
                -3.3060 + ($index * 0.0004),
                102.8760 + ($index * 0.0004)
            );
        }

        foreach (range(1, 3) as $index) {
            $this->createBusiness(
                'DEC-D-D2-' . $index,
                'Kerajinan Rendah D2 ' . $index,
                $typeD,
                $this->districts[1],
                10000000,
                40000000,
                4000000,
                1,
                -3.3070 + ($index * 0.0004),
                102.8770 + ($index * 0.0004)
            );
        }

        $before = $this->sourceSnapshot();

        $data = app(AdminDinasDecisionAnalyticsService::class)->build([
            'type_id' => $typeA->id,
            'district_id' => $this->districts[1]->id,
            'radius_meters' => 500,
        ], true, true);

        $this->assertSame($typeA->id, $data['selected_type']['id']);
        $this->assertSame($this->districts[1]->id, $data['selected_district']['id']);
        $this->assertSame(
            'year_1_baseline_cross_sectional_spatial_decision_support',
            $data['methodology']['scope']
        );
        $this->assertFalse($data['methodology']['forecasting']);
        $this->assertFalse($data['methodology']['automatic_recommendation']);

        $districtTwo = collect($data['competition_by_district'])
            ->firstWhere('id', $this->districts[1]->id);

        $this->assertNotNull($districtTwo);
        $this->assertSame(3, $districtTwo['business_count']);
        $this->assertSame('Rendah', $districtTwo['density_level']);
        $this->assertTrue($districtTwo['potential_relative']);
        $this->assertSame(
            'Indikasi potensi wilayah relatif',
            $districtTwo['context_label']
        );

        $typeOpportunity = collect($data['opportunity_types'])
            ->firstWhere('type_id', $typeA->id);

        $this->assertNotNull($typeOpportunity);
        $this->assertTrue($typeOpportunity['low_count_group']);
        $this->assertTrue($typeOpportunity['potential_relative']);
        $this->assertSame(
            'Indikasi potensi relatif',
            $typeOpportunity['context_label']
        );

        $lowActivity = collect($data['opportunity_types'])
            ->firstWhere('type_id', $typeD->id);

        $this->assertNotNull($lowActivity);
        $this->assertTrue($lowActivity['low_count_group']);
        $this->assertFalse($lowActivity['potential_relative']);
        $this->assertSame('Aktivitas relatif rendah', $lowActivity['context_label']);

        $this->assertTrue($data['micro_spatial']['available']);
        $this->assertSame(500, $data['micro_spatial']['radius_meters']);
        $this->assertSame(3, $data['micro_spatial']['focus_count']);
        $this->assertGreaterThanOrEqual(21, $data['micro_spatial']['pool_count']);

        $micro = collect($data['micro_spatial']['rows'])
            ->firstWhere('business_name', 'Kuliner D2 1');

        $this->assertNotNull($micro);
        $this->assertGreaterThanOrEqual(2, $micro['neighbors_500m']);
        $this->assertNotNull($micro['nearest_same_type_meters']);

        $this->assertSame($before, $this->sourceSnapshot());

        $response = $this->actingAs($admin)
            ->get(route('admin-dinas.analytics.decision', [
                'type_id' => $typeA->id,
                'district_id' => $this->districts[1]->id,
                'radius_meters' => 500,
            ]));

        $response
            ->assertOk()
            ->assertSee('Analitik Keputusan Admin Dinas')
            ->assertSee('Persaingan & Konsentrasi', false)
            ->assertSee('Potensi Relatif')
            ->assertSee('Micro-Spatial Analytics')
            ->assertSee('Indikasi potensi relatif')
            ->assertSee('Kuliner D2 1');

        $html = $response->getContent();
        $offcanvasStart = strpos($html, '<nav class="dashboard-offcanvas-menu"');

        $this->assertNotFalse($offcanvasStart);

        $offcanvas = substr($html, $offcanvasStart);

        $this->assertStringContainsString('<strong>Dasbor</strong>', $offcanvas);
        $this->assertStringContainsString(
            '<strong>Analitik Keputusan</strong>',
            $offcanvas
        );
        $this->assertStringContainsString(
            '<strong>Peta Wilayah</strong>',
            $offcanvas
        );
    }

    public function test_sensitive_financial_and_coordinate_components_follow_existing_permissions(): void
    {
        $admin = $this->createAdminDinas(
            'decision-limited@example.test',
            false,
            false
        );

        $type = $this->createType('Jenis Terbatas');

        foreach (range(1, 3) as $index) {
            $this->createBusiness(
                'DEC-LIMIT-' . $index,
                'Usaha Terbatas ' . $index,
                $type,
                $this->districts[0],
                10000000,
                100000000,
                10000000,
                2,
                -3.2900 + ($index * 0.0002),
                102.8600 + ($index * 0.0002)
            );
        }

        $data = app(AdminDinasDecisionAnalyticsService::class)->build([
            'type_id' => $type->id,
            'district_id' => $this->districts[0]->id,
            'radius_meters' => 500,
        ], false, false);

        $this->assertFalse($data['can_view_financial']);
        $this->assertFalse($data['can_view_coordinates']);
        $this->assertNull($data['competition_summary']['economic_metric']);
        $this->assertFalse($data['micro_spatial']['available']);
        $this->assertSame(
            'coordinate_permission_required',
            $data['micro_spatial']['reason']
        );
        $this->assertFalse(
            collect($data['competition_by_district'])
                ->contains(fn (array $row): bool => $row['potential_relative'])
        );

        $this->actingAs($admin)
            ->get(route('admin-dinas.analytics.decision', [
                'type_id' => $type->id,
                'district_id' => $this->districts[0]->id,
            ]))
            ->assertOk()
            ->assertSee('memerlukan izin')
            ->assertDontSee('Omzet bulanan baseline');
    }

    private function replaceLocationTableWithProductionAlignedTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('umkm_locations');

        Schema::create('umkm_locations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('umkm_id');
            $table->unsignedBigInteger('province_region_id');
            $table->unsignedBigInteger('city_region_id');
            $table->unsignedBigInteger('district_region_id')->nullable();
            $table->unsignedBigInteger('village_region_id')->nullable();
            $table->text('address_detail')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('coordinate_status')->default('belum_terpetakan');
            $table->string('status_data')->default('diajukan');
            $table->timestamps();
            $table->softDeletes();

            $table->index('umkm_id');
            $table->index('district_region_id');
            $table->index('village_region_id');
            $table->index('coordinate_status');
        });

        Schema::enableForeignKeyConstraints();
    }

    private function createRegionHierarchy(): void
    {
        $this->province = Region::query()->create([
            'code' => '16',
            'name' => 'Sumatera Selatan',
            'level' => Region::LEVEL_PROVINCE,
            'parent_code' => null,
            'province_code' => '16',
            'source' => 'test',
            'is_active' => true,
        ]);

        $this->city = Region::query()->create([
            'code' => '16.73',
            'name' => 'Kota Lubuk Linggau',
            'level' => Region::LEVEL_CITY,
            'parent_code' => '16',
            'province_code' => '16',
            'city_code' => '16.73',
            'source' => 'test',
            'is_active' => true,
        ]);

        foreach ([
            ['16.73.01', 'Lubuk Linggau Timur I'],
            ['16.73.02', 'Lubuk Linggau Timur II'],
            ['16.73.03', 'Lubuk Linggau Barat I'],
            ['16.73.04', 'Lubuk Linggau Barat II'],
        ] as [$code, $name]) {
            $district = Region::query()->create([
                'code' => $code,
                'name' => $name,
                'level' => Region::LEVEL_DISTRICT,
                'parent_code' => '16.73',
                'province_code' => '16',
                'city_code' => '16.73',
                'district_code' => $code,
                'source' => 'test',
                'is_active' => true,
            ]);

            $this->districts[] = $district;

            $villageCode = $code . '.1001';
            $village = Region::query()->create([
                'code' => $villageCode,
                'name' => 'Kelurahan Fixture ' . $name,
                'level' => Region::LEVEL_VILLAGE,
                'parent_code' => $code,
                'province_code' => '16',
                'city_code' => '16.73',
                'district_code' => $code,
                'village_code' => $villageCode,
                'source' => 'test',
                'is_active' => true,
            ]);

            $this->villagesByDistrictId[$district->id] = $village;
        }
    }

    private function createType(string $name): BusinessTypeReference
    {
        return BusinessTypeReference::query()->create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'is_active' => true,
        ]);
    }

    private function createBusiness(
        string $code,
        string $name,
        BusinessTypeReference $type,
        Region $district,
        float $capital,
        float $annualSales,
        float $monthlyRevenue,
        int $employees,
        float $latitude,
        float $longitude
    ): Umkm {
        $umkm = Umkm::query()->create([
            'umkm_code' => $code,
            'business_name' => $name,
            'status_data' => 'resmi',
            'quality_status' => 'lengkap_terpetakan',
        ]);

        UmkmBusinessClassification::query()->create([
            'umkm_id' => $umkm->id,
            'business_type_id' => $type->id,
            'business_category_id' => $this->category->id,
            'is_primary' => true,
            'status_data' => 'resmi',
        ]);

        UmkmLocation::query()->create([
            'umkm_id' => $umkm->id,
            'province_region_id' => $this->province->id,
            'city_region_id' => $this->city->id,
            'district_region_id' => $district->id,
            'village_region_id' => $this->villagesByDistrictId[$district->id]->id,
            'address_detail' => 'Alamat fixture ' . $code,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'coordinate_status' => 'terpetakan',
            'status_data' => 'resmi',
        ]);

        $baseline = [
            'umkm_id' => $umkm->id,
            'employee_count' => $employees,
            'capital_amount' => $capital,
            'annual_sales_amount' => $annualSales,
            'baseline_monthly_revenue' => $monthlyRevenue,
            'status_data' => 'resmi',
        ];

        if (Schema::hasColumn('umkm_baseline_profiles', 'created_at')) {
            $baseline['created_at'] = now();
        }
        if (Schema::hasColumn('umkm_baseline_profiles', 'updated_at')) {
            $baseline['updated_at'] = now();
        }

        DB::table('umkm_baseline_profiles')->insert($baseline);

        return $umkm;
    }

    private function createAdminDinas(
        string $email,
        bool $withFinancial,
        bool $withCoordinate
    ): User {
        $user = User::query()->create([
            'name' => 'Admin Dinas Decision',
            'email' => $email,
            'username' => str_replace(['@', '.'], '-', $email),
            'password' => Hash::make('DecisionPassword123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        DB::table('roles')->updateOrInsert(
            ['code' => 'admin_dinas'],
            [
                'name' => 'Admin Dinas',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $roleId = (int) DB::table('roles')
            ->where('code', 'admin_dinas')
            ->value('id');

        $permissionCodes = ['umkm.read.official'];

        if ($withFinancial) {
            $permissionCodes[] = 'umkm.sensitive.financial';
        }

        if ($withCoordinate) {
            $permissionCodes[] = 'umkm.sensitive.coordinate';
        }

        foreach ($permissionCodes as $code) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => ucwords(str_replace(['.', '_'], ' ', $code)),
                    'module' => 'umkm',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $permissionId = (int) DB::table('permissions')
                ->where('code', $code)
                ->value('id');

            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->fresh();
    }

    private function sourceSnapshot(): array
    {
        return [
            'umkms' => DB::table('umkms')
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'baseline' => DB::table('umkm_baseline_profiles')
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'locations' => DB::table('umkm_locations')
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'classifications' => DB::table('umkm_business_classifications')
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
        ];
    }
}