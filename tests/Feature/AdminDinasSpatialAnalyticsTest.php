<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Reference\Region;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmLocation;
use App\Models\User;
use App\Services\AdminDinas\AdminDinasSpatialAnalyticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDinasSpatialAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_spatial_analytics_route_is_registered_get_only(): void
    {
        $this->assertTrue(Route::has('admin-dinas.analytics.spatial'));

        $route = Route::getRoutes()->getByName('admin-dinas.analytics.spatial');

        $this->assertNotNull($route);
        $this->assertSame('admin-dinas/analytics/spatial', $route->uri());
        $this->assertEmpty(
            array_intersect(
                $route->methods(),
                ['POST', 'PUT', 'PATCH', 'DELETE']
            )
        );

        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains('role:admin_dinas', $middleware);
        $this->assertContains('permission:umkm.read.official', $middleware);
    }

    public function test_coordinate_mapped_rule_requires_status_and_both_coordinates(): void
    {
        $this->replaceLocationTableWithProductionAlignedTestSchema();

        $regions = $this->createLocationHierarchy();

        $mapped = $this->createUmkm('SPATIAL-001', 'UMKM MAPPED');
        $missingLatitude = $this->createUmkm('SPATIAL-002', 'UMKM NO LAT');
        $wrongStatus = $this->createUmkm('SPATIAL-003', 'UMKM WRONG STATUS');
        $noLocation = $this->createUmkm('SPATIAL-004', 'UMKM NO LOCATION');

        UmkmLocation::query()->create(array_merge($regions, [
            'umkm_id' => $mapped->id,
            'address_detail' => 'Alamat fixture mapped',
            'latitude' => -3.2900000,
            'longitude' => 102.8600000,
            'coordinate_status' => 'terpetakan',
            'status_data' => 'diajukan',
        ]));

        UmkmLocation::query()->create(array_merge($regions, [
            'umkm_id' => $missingLatitude->id,
            'address_detail' => 'Alamat fixture tanpa latitude',
            'latitude' => null,
            'longitude' => 102.8700000,
            'coordinate_status' => 'terpetakan',
            'status_data' => 'diajukan',
        ]));

        UmkmLocation::query()->create(array_merge($regions, [
            'umkm_id' => $wrongStatus->id,
            'address_detail' => 'Alamat fixture status belum terpetakan',
            'latitude' => -3.2800000,
            'longitude' => 102.8800000,
            'coordinate_status' => 'belum_terpetakan',
            'status_data' => 'diajukan',
        ]));

        $data = app(AdminDinasSpatialAnalyticsService::class)
            ->build([], false, false);

        $this->assertSame(4, $data['summary']['total_umkm']);
        $this->assertSame(3, $data['summary']['administrative_associated']);
        $this->assertSame(1, $data['summary']['administrative_unassociated']);
        $this->assertSame(1, $data['summary']['coordinate_mapped']);
        $this->assertSame(3, $data['summary']['coordinate_unmapped']);
        $this->assertSame(25.0, $data['summary']['coordinate_mapped_percent']);
        $this->assertFalse($data['map']['coordinate_access']);
        $this->assertSame([], $data['map']['points']);

        $withCoordinates = app(AdminDinasSpatialAnalyticsService::class)
            ->build([], false, true);

        $this->assertTrue($withCoordinates['map']['coordinate_access']);
        $this->assertCount(1, $withCoordinates['map']['points']);
        $this->assertSame(
            $mapped->id,
            $withCoordinates['map']['points'][0]['umkm_id']
        );

        $this->assertNotNull($noLocation);
    }

    public function test_admin_dinas_without_coordinate_permission_gets_administrative_map_only(): void
    {
        $user = $this->createAdminDinasUser(
            'spatial-read@example.test',
            false
        );

        $this->actingAs($user)
            ->get('/admin-dinas/analytics/spatial')
            ->assertOk()
            ->assertSeeText('Peta Sebaran UMKM', false)
            ->assertSee('Peta Wilayah')
            ->assertSee('Data wilayah dan titik lokasi adalah informasi yang berbeda')
            ->assertSee('Titik lokasi masing-masing usaha disembunyikan')
            ->assertDontSee('Tampilkan titik lokasi');
    }

    public function test_coordinate_permission_enables_point_layer_control(): void
    {
        $user = $this->createAdminDinasUser(
            'spatial-coordinate@example.test',
            true
        );

        $this->actingAs($user)
            ->get('/admin-dinas/analytics/spatial')
            ->assertOk()
            ->assertSee('Tampilkan titik lokasi')
            ->assertSee(
                'Titik biru = UMKM yang memiliki titik lokasi lengkap'
            );
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

    private function createLocationHierarchy(): array
    {
        $province = Region::query()->create([
            'code' => '16',
            'name' => 'Sumatera Selatan',
            'level' => Region::LEVEL_PROVINCE,
            'parent_code' => null,
            'province_code' => '16',
            'city_code' => null,
            'district_code' => null,
            'village_code' => null,
            'source' => 'test',
            'is_active' => true,
        ]);

        $city = Region::query()->create([
            'code' => '16.73',
            'name' => 'Kota Lubuk Linggau',
            'level' => Region::LEVEL_CITY,
            'parent_code' => '16',
            'province_code' => '16',
            'city_code' => '16.73',
            'district_code' => null,
            'village_code' => null,
            'source' => 'test',
            'is_active' => true,
        ]);

        $district = Region::query()->create([
            'code' => '16.73.01',
            'name' => 'Lubuk Linggau Timur I',
            'level' => Region::LEVEL_DISTRICT,
            'parent_code' => '16.73',
            'province_code' => '16',
            'city_code' => '16.73',
            'district_code' => '16.73.01',
            'village_code' => null,
            'source' => 'test',
            'is_active' => true,
        ]);

        $village = Region::query()->create([
            'code' => '16.73.01.1001',
            'name' => 'Watervang',
            'level' => Region::LEVEL_VILLAGE,
            'parent_code' => '16.73.01',
            'province_code' => '16',
            'city_code' => '16.73',
            'district_code' => '16.73.01',
            'village_code' => '16.73.01.1001',
            'source' => 'test',
            'is_active' => true,
        ]);

        return [
            'province_region_id' => $province->id,
            'city_region_id' => $city->id,
            'district_region_id' => $district->id,
            'village_region_id' => $village->id,
        ];
    }

    private function createUmkm(string $code, string $name): Umkm
    {
        return Umkm::query()->create([
            'umkm_code' => $code,
            'business_name' => $name,
            'status_data' => 'resmi',
            'quality_status' => 'belum_tersedia',
        ]);
    }

    private function createAdminDinasUser(
        string $email,
        bool $withCoordinate
    ): User {
        $user = User::query()->create([
            'name' => 'Admin Dinas Spatial Test',
            'email' => $email,
            'username' => str_replace(['@', '.'], '-', $email),
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

        $read = Permission::query()->firstOrCreate(
            ['code' => 'umkm.read.official'],
            [
                'name' => 'Read Official UMKM',
                'module' => 'umkm',
            ]
        );

        $role->permissions()->syncWithoutDetaching([$read->id]);

        if ($withCoordinate) {
            $coordinate = Permission::query()->firstOrCreate(
                ['code' => 'umkm.sensitive.coordinate'],
                [
                    'name' => 'View Sensitive Coordinate',
                    'module' => 'umkm',
                ]
            );

            $role->permissions()->syncWithoutDetaching([$coordinate->id]);
        }

        $user->roles()->attach($role->id);

        return $user;
    }
}
