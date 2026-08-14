<?php

namespace Tests\Feature;

use App\Support\PublicLanding\PublicLandingAdvancedAnalytics;
use App\Support\PublicLanding\PublicLandingDataFreshness;
use App\Support\PublicLanding\PublicLandingMetricQuery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicLandingAnalyticsIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'lss_sync_record_states',
            'lss_sync_runs',
            'umkm_data_quality_flags',
            'umkm_legalities',
            'umkm_business_classifications',
            'business_category_references',
            'business_type_references',
            'marketing_method_references',
            'umkm_baseline_profiles',
            'umkm_locations',
            'regions',
            'umkms',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function test_workforce_total_does_not_inherit_distinct_state_from_count_query(): void
    {
        Schema::create('umkms', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('umkm_baseline_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('umkm_id');
            $table->unsignedInteger('employee_count')->nullable();
        });

        DB::table('umkms')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);
        DB::table('umkm_baseline_profiles')->insert([
            ['umkm_id' => 1, 'employee_count' => 1],
            ['umkm_id' => 2, 'employee_count' => 1],
            ['umkm_id' => 3, 'employee_count' => 2],
        ]);

        $result = PublicLandingAdvancedAnalytics::workforce([], DB::table('umkms'), 3);

        $this->assertSame(3, $result['valid_filled_total']);
        $this->assertSame(4, $result['total_workers']);
        $this->assertSame(1.0, $result['median_workers']);
    }

    public function test_public_metric_excludes_inactive_lss_records_but_keeps_non_lss_records(): void
    {
        config()->set('umkm.landing_region.province_code', '16');
        config()->set('umkm.landing_region.province_name', 'Sumatera Selatan');
        config()->set('umkm.landing_region.city_code', '16.73');
        config()->set('umkm.landing_region.city_name', 'Kota Lubuk Linggau');
        config()->set('umkm.data.operational_statuses', ['resmi', 'terbatas']);

        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('level')->nullable();
            $table->string('parent_code')->nullable();
            $table->string('city_code')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('umkms', function (Blueprint $table): void {
            $table->id();
            $table->string('status_data');
            $table->string('source_system')->nullable();
            $table->boolean('source_active')->default(true);
        });

        Schema::create('umkm_data_quality_flags', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('umkm_id');
            $table->string('flag_group');
            $table->string('severity');
            $table->string('status')->default('open');
        });

        Schema::create('umkm_locations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('umkm_id');
            $table->unsignedBigInteger('city_region_id')->nullable();
            $table->unsignedBigInteger('district_region_id')->nullable();
            $table->unsignedBigInteger('village_region_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('coordinate_status')->nullable();
        });

        DB::table('regions')->insert([
            'id' => 1,
            'code' => '16.73',
            'name' => 'Kota Lubuk Linggau',
            'level' => 'city',
            'city_code' => '16.73',
            'is_active' => 1,
        ]);

        DB::table('umkms')->insert([
            ['id' => 1, 'status_data' => 'terbatas', 'source_system' => 'LSS', 'source_active' => 1],
            ['id' => 2, 'status_data' => 'terbatas', 'source_system' => 'LSS', 'source_active' => 0],
            ['id' => 3, 'status_data' => 'resmi', 'source_system' => null, 'source_active' => 1],
        ]);

        DB::table('umkm_data_quality_flags')->insert([
            ['umkm_id' => 1, 'flag_group' => 'coordinate', 'severity' => 'info', 'status' => 'open'],
            ['umkm_id' => 1, 'flag_group' => 'identity', 'severity' => 'info', 'status' => 'open'],
            ['umkm_id' => 2, 'flag_group' => 'coordinate', 'severity' => 'info', 'status' => 'open'],
            ['umkm_id' => 3, 'flag_group' => 'media', 'severity' => 'info', 'status' => 'open'],
        ]);

        DB::table('umkm_locations')->insert([
            ['umkm_id' => 1, 'city_region_id' => 1, 'latitude' => -3.29, 'longitude' => 102.86, 'coordinate_status' => 'terpetakan'],
            ['umkm_id' => 2, 'city_region_id' => 1, 'latitude' => -3.28, 'longitude' => 102.87, 'coordinate_status' => 'terpetakan'],
            ['umkm_id' => 3, 'city_region_id' => 1, 'latitude' => -3.27, 'longitude' => 102.88, 'coordinate_status' => 'terpetakan'],
        ]);

        $payload = PublicLandingMetricQuery::payload(['scope' => 'city']);

        $this->assertSame(2, $payload['summary']['total']);
        $this->assertSame(2, $payload['summary']['mapped']);
        $this->assertSame(3, $payload['analytics']['data_readiness']['quality_summary']['flag_count']);
        $this->assertSame(2, $payload['analytics']['data_readiness']['quality_summary']['affected_umkm_count']);
    }

    public function test_freshness_uses_latest_completed_sync_not_request_time(): void
    {
        Schema::create('lss_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('snapshot_id');
            $table->string('source_system')->default('LSS');
            $table->string('status')->default('prepared');
            $table->timestamp('completed_at')->nullable();
        });

        DB::table('lss_sync_runs')->insert([
            [
                'snapshot_id' => 'LSS-OLD',
                'source_system' => 'LSS',
                'status' => 'completed',
                'completed_at' => '2026-08-13 20:00:00',
            ],
            [
                'snapshot_id' => 'LSS-NEW',
                'source_system' => 'LSS',
                'status' => 'completed',
                'completed_at' => '2026-08-14 01:34:00',
            ],
            [
                'snapshot_id' => 'LSS-PENDING',
                'source_system' => 'LSS',
                'status' => 'prepared',
                'completed_at' => '2026-08-14 09:00:00',
            ],
        ]);

        $freshness = PublicLandingDataFreshness::latest();

        $this->assertSame('LSS-NEW', $freshness['snapshot_id']);
        $this->assertStringContainsString('14/08/2026', $freshness['label']);
    }

    public function test_public_legality_payload_exposes_identification_count_not_storage_representation(): void
    {
        Schema::create('umkms', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('umkm_legalities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('umkm_id');
            $table->string('nib_number')->nullable();
            $table->string('nib_number_masked')->nullable();
        });

        DB::table('umkms')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);
        DB::table('umkm_legalities')->insert([
            ['umkm_id' => 1, 'nib_number' => 'RAW-ONE', 'nib_number_masked' => null],
            ['umkm_id' => 2, 'nib_number' => null, 'nib_number_masked' => '****1234'],
        ]);

        $result = PublicLandingAdvancedAnalytics::legality([], DB::table('umkms'), 3);

        $this->assertSame(2, $result['legalities_total']);
        $this->assertSame(2, $result['nib_identified_total']);
        $this->assertSame(1, $result['unidentified_total']);
        $this->assertArrayNotHasKey('nib_filled', $result);
        $this->assertArrayNotHasKey('nib_masked_filled', $result);
    }
}
