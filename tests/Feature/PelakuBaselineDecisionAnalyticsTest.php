<?php

namespace Tests\Feature;

use App\Models\Reference\BusinessCategoryReference;
use App\Models\Reference\BusinessTypeReference;
use App\Models\Reference\Region;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmAccountClaim;
use App\Models\Umkm\UmkmBusinessClassification;
use App\Models\Umkm\UmkmDataQualityFlag;
use App\Models\Umkm\UmkmLocation;
use App\Models\Umkm\UmkmUserLink;
use App\Models\User;
use App\Services\PelakuUmkm\PelakuBaselineDecisionAnalyticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PelakuBaselineDecisionAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private int $roleId;
    private int $workspacePermissionId;
    private Region $province;
    private Region $city;
    private BusinessCategoryReference $category;
    private array $districts = [];
    private array $villagesByDistrictId = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->replaceLocationTableWithProductionAlignedTestSchema();

        DB::table('roles')->updateOrInsert(
            ['code' => 'pelaku_umkm'],
            [
                'name' => 'Pelaku UMKM',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->roleId = (int) DB::table('roles')->where('code', 'pelaku_umkm')->value('id');
        $this->workspacePermissionId = (int) DB::table('permissions')
            ->where('code', 'umkm.workspace.access')
            ->value('id');

        $this->assertGreaterThan(0, $this->workspacePermissionId);

        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $this->roleId,
                'permission_id' => $this->workspacePermissionId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->createRegionHierarchy();
        $this->category = BusinessCategoryReference::query()->create([
            'name' => 'Kategori Analitik Fixture',
            'slug' => 'kategori-analitik-fixture',
            'is_active' => true,
        ]);
        $this->assertTrue(Schema::hasColumn('umkm_baseline_profiles', 'capital_amount'));
        $this->assertTrue(Schema::hasColumn('umkm_baseline_profiles', 'annual_sales_amount'));
        $this->assertTrue(Schema::hasColumn('umkm_baseline_profiles', 'baseline_monthly_revenue'));
    }

    public function test_verified_owner_receives_baseline_position_competition_and_opportunity_without_peer_identity(): void
    {
        $user = $this->createPelaku('analytics-owner@example.test');
        $typeA = $this->createType('Kuliner Olahan');
        $typeB = $this->createType('Jasa Kreatif');
        $typeC = $this->createType('Reparasi');
        $typeD = $this->createType('Kerajinan Baseline Rendah');

        $owned = $this->createBusiness(
            'AN-OWN-001',
            'Usaha Milik Analitik',
            $typeA,
            $this->districts[0],
            50000000,
            240000000,
            20000000,
            4
        );
        $this->createVerifiedBinding($user, $owned, 'AN-CLAIM-001');

        foreach (range(1, 6) as $index) {
            $this->createBusiness(
                'AN-A-D1-' . $index,
                'Peer Rahasia D1 ' . $index,
                $typeA,
                $this->districts[0],
                30000000 + ($index * 1000000),
                120000000 + ($index * 1000000),
                10000000,
                3
            );
        }

        foreach (range(1, 3) as $index) {
            $this->createBusiness(
                'AN-A-D2-' . $index,
                'Peer Rahasia D2 ' . $index,
                $typeA,
                $this->districts[1],
                45000000,
                300000000,
                20000000,
                5
            );
        }

        foreach (range(1, 5) as $index) {
            $this->createBusiness(
                'AN-A-D3-' . $index,
                'Peer Rahasia D3 ' . $index,
                $typeA,
                $this->districts[2],
                40000000,
                220000000,
                15000000,
                4
            );
        }

        foreach (range(1, 7) as $index) {
            $this->createBusiness(
                'AN-A-D4-' . $index,
                'Peer Rahasia D4 ' . $index,
                $typeA,
                $this->districts[3],
                25000000,
                100000000,
                8000000,
                2
            );
        }

        foreach (range(1, 6) as $index) {
            $this->createBusiness(
                'AN-B-D2-' . $index,
                'Jasa Kreatif Peer ' . $index,
                $typeB,
                $this->districts[1],
                20000000,
                90000000,
                10000000,
                2
            );
        }

        foreach (range(1, 3) as $index) {
            $this->createBusiness(
                'AN-C-D2-' . $index,
                'Reparasi Peer ' . $index,
                $typeC,
                $this->districts[1],
                35000000,
                190000000,
                18000000,
                3
            );
        }

        foreach (range(1, 3) as $index) {
            $this->createBusiness(
                'AN-D-D2-' . $index,
                'Kerajinan Baseline Rendah ' . $index,
                $typeD,
                $this->districts[1],
                10000000,
                40000000,
                4000000,
                1
            );
        }

        $sourceBefore = $this->sourceSnapshot();

        $data = app(PelakuBaselineDecisionAnalyticsService::class)->build($user, [
            'umkm_id' => $owned->id,
            'type_id' => $typeA->id,
            'district_id' => $this->districts[1]->id,
        ]);

        $this->assertSame($owned->id, $data['selected_umkm']['id']);
        $this->assertSame($typeA->id, $data['selected_type']['id']);
        $this->assertFalse($data['methodology']['longitudinal_analysis']);
        $this->assertFalse($data['methodology']['forecasting']);
        $this->assertFalse($data['methodology']['automatic_recommendation']);
        $this->assertSame('baseline_cross_sectional_spatial', $data['methodology']['scope']);
        $this->assertGreaterThanOrEqual(3, $data['position']['peer_count']);
        $this->assertTrue($data['position']['metrics']['monthly_revenue']['peer_visible']);

        $districtTwo = collect($data['competition_by_district'])
            ->firstWhere('id', $this->districts[1]->id);
        $this->assertNotNull($districtTwo);
        $this->assertSame(3, $districtTwo['business_count']);
        $this->assertSame('Rendah', $districtTwo['density_level']);
        $this->assertSame('Indikasi potensi wilayah relatif', $districtTwo['context_label']);

        $typeOpportunity = collect($data['opportunity_types'])->firstWhere('type_id', $typeA->id);
        $this->assertNotNull($typeOpportunity);
        $this->assertTrue($typeOpportunity['low_count_group']);
        $this->assertTrue($typeOpportunity['potential_relative']);
        $this->assertSame('Indikasi potensi relatif', $typeOpportunity['context_label']);

        $lowActivity = collect($data['opportunity_types'])->firstWhere('type_id', $typeD->id);
        $this->assertNotNull($lowActivity);
        $this->assertTrue($lowActivity['low_count_group']);
        $this->assertFalse($lowActivity['potential_relative']);
        $this->assertSame('Aktivitas relatif rendah', $lowActivity['context_label']);

        $this->actingAs($user)
            ->get(route('pelaku-umkm.analytics.index', [
                'umkm_id' => $owned->id,
                'type_id' => $typeA->id,
                'district_id' => $this->districts[1]->id,
            ]))
            ->assertOk()
            ->assertSee('Analitik Keputusan Pelaku UMKM')
            ->assertSee('Persaingan Usaha Sejenis')
            ->assertSee('Potensi Jenis Usaha')
            ->assertSee('Indikasi potensi relatif')
            ->assertDontSee('Peer Rahasia D1 1')
            ->assertDontSee('Peer Rahasia D2 1');

        $this->assertEquals($sourceBefore, $this->sourceSnapshot());
    }

    public function test_small_peer_group_suppresses_financial_aggregates(): void
    {
        $user = $this->createPelaku('privacy-owner@example.test');
        $type = $this->createType('Usaha Privasi');
        $owned = $this->createBusiness(
            'AN-PRIV-OWN',
            'Usaha Privasi Milik',
            $type,
            $this->districts[0],
            10000000,
            50000000,
            5000000,
            1
        );
        $this->createVerifiedBinding($user, $owned, 'AN-PRIV-CLAIM');

        foreach (range(1, 2) as $index) {
            $this->createBusiness(
                'AN-PRIV-' . $index,
                'Peer Privasi ' . $index,
                $type,
                $this->districts[1],
                20000000,
                70000000,
                7000000,
                2
            );
        }

        $data = app(PelakuBaselineDecisionAnalyticsService::class)->build($user, [
            'umkm_id' => $owned->id,
            'type_id' => $type->id,
            'district_id' => $this->districts[1]->id,
        ]);

        $this->assertSame(2, $data['position']['peer_count']);
        $this->assertTrue($data['position']['privacy_suppressed']);
        $this->assertFalse($data['position']['metrics']['capital']['peer_visible']);
        $this->assertNull($data['position']['metrics']['capital']['peer_median']);

        $district = collect($data['competition_by_district'])
            ->firstWhere('id', $this->districts[1]->id);
        $this->assertTrue($district['privacy_suppressed']);
        $this->assertFalse($district['capital']['visible']);
        $this->assertNull($district['capital']['median']);
        $this->assertSame('Kepadatan teridentifikasi; agregat ekonomi dibatasi', $district['context_label']);
    }

    public function test_quality_flag_preserves_raw_value_inside_aggregate_and_marks_warning(): void
    {
        $user = $this->createPelaku('quality-owner@example.test');
        $type = $this->createType('Usaha Mutu');
        $owned = $this->createBusiness(
            'AN-Q-OWN',
            'Usaha Mutu Milik',
            $type,
            $this->districts[0],
            100,
            100,
            50,
            1
        );
        $this->createVerifiedBinding($user, $owned, 'AN-Q-CLAIM');

        $this->createBusiness('AN-Q-1', 'Peer Mutu 1', $type, $this->districts[0], 100, 100, 100, 1);
        $this->createBusiness('AN-Q-2', 'Peer Mutu 2', $type, $this->districts[0], 200, 200, 200, 1);
        $anomaly = $this->createBusiness(
            'AN-Q-3',
            'Peer Mutu Anomali',
            $type,
            $this->districts[0],
            300,
            300,
            999999999,
            1
        );

        UmkmDataQualityFlag::query()->create([
            'umkm_id' => $anomaly->id,
            'flag_code' => 'SOURCE_FINANCIAL_ANOMALY',
            'flag_group' => 'financial',
            'severity' => 'warning',
            'description' => 'Nilai sumber ditandai anomali untuk fixture.',
            'detected_value' => '999999999',
            'status' => 'open',
            'source_type' => 'auto',
            'detected_at' => now(),
            'last_checked_at' => now(),
        ]);

        $data = app(PelakuBaselineDecisionAnalyticsService::class)->build($user, [
            'umkm_id' => $owned->id,
            'type_id' => $type->id,
            'district_id' => $this->districts[0]->id,
        ]);

        $row = collect($data['competition_by_district'])
            ->firstWhere('id', $this->districts[0]->id);

        $this->assertTrue($row['monthly_revenue']['visible']);
        $this->assertSame(1000000349.0, $row['monthly_revenue']['total']);
        $this->assertSame(1, $row['quality_affected']);
        $this->assertTrue($row['quality_warning']);
        $this->assertTrue($data['quality_warning']);
        $this->assertFalse($data['methodology']['anomalies_excluded']);
        $this->assertTrue($data['methodology']['source_values_preserved']);
    }

    public function test_analytics_cannot_select_umkm_outside_verified_ownership(): void
    {
        $user = $this->createPelaku('isolation-analytics@example.test');
        $type = $this->createType('Usaha Isolasi');
        $owned = $this->createBusiness(
            'AN-ISO-OWN',
            'Usaha Isolasi Milik',
            $type,
            $this->districts[0],
            1000,
            1000,
            1000,
            1
        );
        $other = $this->createBusiness(
            'AN-ISO-OTHER',
            'Usaha Bukan Milik',
            $type,
            $this->districts[1],
            1000,
            1000,
            1000,
            1
        );
        $this->createVerifiedBinding($user, $owned, 'AN-ISO-CLAIM');

        $this->actingAs($user)
            ->get(route('pelaku-umkm.analytics.index', ['umkm_id' => $other->id]))
            ->assertNotFound();
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
        int $employees
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
            'latitude' => -3.2900000,
            'longitude' => 102.8600000,
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

    private function createPelaku(string $email): User
    {
        $user = User::query()->create([
            'name' => 'Pelaku Analitik',
            'email' => $email,
            'password' => Hash::make('PasswordAnalytics123'),
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
            'name' => 'Reviewer ' . $reference,
            'email' => strtolower($reference) . '@example.test',
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

    private function sourceSnapshot(): array
    {
        return [
            'umkms' => DB::table('umkms')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'baseline' => DB::table('umkm_baseline_profiles')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'locations' => DB::table('umkm_locations')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'classifications' => DB::table('umkm_business_classifications')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'quality_flags' => DB::table('umkm_data_quality_flags')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
        ];
    }
}