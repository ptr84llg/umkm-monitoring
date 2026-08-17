<?php

namespace Tests\Feature;

use App\Models\Umkm\UmkmPerformanceRecordRevision;
use App\Models\User;
use App\Services\Reporting\UmkmPerformanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class PeriodicPerformanceRevisionLineageTest extends TestCase
{
    use RefreshDatabase;

    public function test_periodic_reporting_uses_append_only_revision_lineage_and_current_projection(): void
    {
        $this->assertTrue(Schema::hasTable('umkm_performance_record_revisions'));
        $this->assertTrue(Schema::hasTable('umkm_current_performance_revisions'));

        $user = User::query()->create([
            'name' => 'Reporter 10F',
            'email' => 'reporter-10f@example.test',
            'password' => 'ReporterPassword123',
            'is_active' => true,
        ]);
        $umkmId = DB::table('umkms')->insertGetId([
            'umkm_code' => 'LSS-PERF-10F',
            'business_name' => 'UMKM Performance 10F',
        ]);
        $periodId = DB::table('monitoring_periods')->insertGetId([
            'period_code' => '2026-08-10F',
            'period_name' => 'Periode Uji 10F',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'is_locked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(UmkmPerformanceRevisionService::class);
        $first = $service->appendRevision($umkmId, $periodId, [
            'monthly_revenue' => '1000000.00',
            'worker_count' => 3,
            'production_volume' => 20,
            'status_data' => 'diajukan',
        ], $user->id);

        $firstSnapshot = $first->toArray();
        $this->assertSame(1, $first->revision_no);
        $this->assertNull($first->previous_revision_id);
        $this->assertDatabaseHas('umkm_performance_records', [
            'umkm_id' => $umkmId,
            'monitoring_period_id' => $periodId,
            'worker_count' => 3,
            'production_volume' => 20,
            'status_data' => 'diajukan',
        ]);

        $second = $service->appendRevision($umkmId, $periodId, [
            'worker_count' => 4,
            'status_data' => 'disetujui',
        ], $user->id, 'Koreksi jumlah tenaga kerja setelah verifikasi.');

        $this->assertSame(2, $second->revision_no);
        $this->assertSame($first->id, $second->previous_revision_id);
        $this->assertSame('1000000.00', $second->monthly_revenue);
        $this->assertSame(4, $second->worker_count);
        $this->assertSame(20, $second->production_volume);
        $this->assertEquals($firstSnapshot, $first->fresh()->toArray());
        $this->assertDatabaseHas('umkm_current_performance_revisions', [
            'umkm_id' => $umkmId,
            'monitoring_period_id' => $periodId,
            'performance_revision_id' => $second->id,
        ]);
        $this->assertDatabaseHas('umkm_performance_records', [
            'umkm_id' => $umkmId,
            'monitoring_period_id' => $periodId,
            'worker_count' => 4,
            'production_volume' => 20,
            'status_data' => 'disetujui',
        ]);

        try {
            $first->forceFill(['worker_count' => 99])->save();
            $this->fail('Append-only performance revision must reject update.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }
    }

    public function test_second_revision_requires_reason_and_locked_period_rejects_changes(): void
    {
        $user = User::query()->create([
            'name' => 'Reporter Locked 10F',
            'email' => 'reporter-locked-10f@example.test',
            'password' => 'ReporterPassword123',
            'is_active' => true,
        ]);
        $umkmId = DB::table('umkms')->insertGetId([
            'umkm_code' => 'LSS-PERF-LOCK-10F',
            'business_name' => 'UMKM Performance Lock 10F',
        ]);
        $periodId = DB::table('monitoring_periods')->insertGetId([
            'period_code' => '2026-09-10F',
            'period_name' => 'Periode Uji Lock 10F',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'is_locked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(UmkmPerformanceRevisionService::class);
        $service->appendRevision($umkmId, $periodId, ['worker_count' => 2], $user->id);

        try {
            $service->appendRevision($umkmId, $periodId, ['worker_count' => 3], $user->id);
            $this->fail('Second performance revision without reason must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('revision_reason', $exception->errors());
        }

        DB::table('monitoring_periods')->where('id', $periodId)->update(['is_locked' => true]);

        try {
            $service->appendRevision($umkmId, $periodId, ['worker_count' => 3], $user->id, 'Tidak boleh karena terkunci.');
            $this->fail('Locked monitoring period must reject revision.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('monitoring_period_id', $exception->errors());
        }

        $this->assertSame(1, UmkmPerformanceRecordRevision::query()->count());
    }
}