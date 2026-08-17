<?php

namespace App\Services\Reporting;

use App\Models\Umkm\UmkmCurrentPerformanceRevision;
use App\Models\Umkm\UmkmPerformanceRecordRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use LogicException;

class UmkmPerformanceRevisionService
{
    private const ALLOWED_STATUSES = [
        'draft',
        'diajukan',
        'perlu_perbaikan',
        'disetujui',
        'resmi',
    ];

    public function appendRevision(
        int $umkmId,
        int $monitoringPeriodId,
        array $data,
        ?int $actorUserId = null,
        ?string $revisionReason = null
    ): UmkmPerformanceRecordRevision {
        $this->assertSchema();

        return DB::transaction(function () use (
            $umkmId,
            $monitoringPeriodId,
            $data,
            $actorUserId,
            $revisionReason
        ): UmkmPerformanceRecordRevision {
            $umkm = DB::table('umkms')->where('id', $umkmId)->lockForUpdate()->first();
            if (! $umkm) {
                throw ValidationException::withMessages(['umkm_id' => 'UMKM tidak ditemukan.']);
            }

            $period = DB::table('monitoring_periods')
                ->where('id', $monitoringPeriodId)
                ->lockForUpdate()
                ->first();

            if (! $period) {
                throw ValidationException::withMessages(['monitoring_period_id' => 'Periode monitoring tidak ditemukan.']);
            }

            if ((bool) $period->is_locked) {
                throw ValidationException::withMessages(['monitoring_period_id' => 'Periode monitoring sudah dikunci.']);
            }

            $current = UmkmCurrentPerformanceRevision::query()
                ->where('umkm_id', $umkmId)
                ->where('monitoring_period_id', $monitoringPeriodId)
                ->lockForUpdate()
                ->first();

            $previous = $current
                ? UmkmPerformanceRecordRevision::query()->findOrFail($current->performance_revision_id)
                : null;

            if ($previous && trim((string) $revisionReason) === '') {
                throw ValidationException::withMessages([
                    'revision_reason' => 'Alasan revisi wajib diisi untuk perubahan setelah laporan pertama.',
                ]);
            }

            $snapshot = [
                'monthly_revenue' => array_key_exists('monthly_revenue', $data)
                    ? $this->nullableDecimal($data['monthly_revenue'])
                    : $previous?->monthly_revenue,
                'worker_count' => array_key_exists('worker_count', $data)
                    ? $this->nullableInteger($data['worker_count'])
                    : $previous?->worker_count,
                'production_volume' => array_key_exists('production_volume', $data)
                    ? $this->nullableInteger($data['production_volume'])
                    : $previous?->production_volume,
                'status_data' => (string) ($data['status_data'] ?? $previous?->status_data ?? 'draft'),
            ];

            if (! in_array($snapshot['status_data'], self::ALLOWED_STATUSES, true)) {
                throw ValidationException::withMessages(['status_data' => 'Status laporan periodik tidak valid.']);
            }

            if ($snapshot['worker_count'] !== null && $snapshot['worker_count'] < 0) {
                throw ValidationException::withMessages(['worker_count' => 'Jumlah tenaga kerja tidak boleh negatif.']);
            }

            if ($snapshot['production_volume'] !== null && $snapshot['production_volume'] < 0) {
                throw ValidationException::withMessages(['production_volume' => 'Volume produksi tidak boleh negatif.']);
            }

            $revision = UmkmPerformanceRecordRevision::query()->create([
                'umkm_id' => $umkmId,
                'monitoring_period_id' => $monitoringPeriodId,
                'previous_revision_id' => $previous?->id,
                'revision_no' => ($previous?->revision_no ?? 0) + 1,
                'monthly_revenue' => $snapshot['monthly_revenue'],
                'worker_count' => $snapshot['worker_count'],
                'production_volume' => $snapshot['production_volume'],
                'status_data' => $snapshot['status_data'],
                'submitted_by_user_id' => $actorUserId,
                'submitted_at' => now(),
                'revision_reason' => $previous ? trim((string) $revisionReason) : null,
            ]);

            UmkmCurrentPerformanceRevision::query()->updateOrCreate(
                [
                    'umkm_id' => $umkmId,
                    'monitoring_period_id' => $monitoringPeriodId,
                ],
                [
                    'performance_revision_id' => $revision->id,
                    'updated_by_user_id' => $actorUserId,
                ]
            );

            $projection = [
                'monthly_revenue' => $snapshot['monthly_revenue'],
                'worker_count' => $snapshot['worker_count'],
                'production_volume' => $snapshot['production_volume'],
                'status_data' => $snapshot['status_data'],
                'updated_at' => now(),
            ];

            $exists = DB::table('umkm_performance_records')
                ->where('umkm_id', $umkmId)
                ->where('monitoring_period_id', $monitoringPeriodId)
                ->exists();

            if ($exists) {
                DB::table('umkm_performance_records')
                    ->where('umkm_id', $umkmId)
                    ->where('monitoring_period_id', $monitoringPeriodId)
                    ->update($projection);
            } else {
                DB::table('umkm_performance_records')->insert(array_merge($projection, [
                    'umkm_id' => $umkmId,
                    'monitoring_period_id' => $monitoringPeriodId,
                    'created_at' => now(),
                ]));
            }

            return $revision;
        });
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw ValidationException::withMessages(['performance' => 'Nilai numerik laporan periodik tidak valid.']);
        }

        return (int) $value;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw ValidationException::withMessages(['monthly_revenue' => 'Nilai omzet bulanan tidak valid.']);
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function assertSchema(): void
    {
        foreach ([
            'umkms',
            'monitoring_periods',
            'umkm_performance_records',
            'umkm_performance_record_revisions',
            'umkm_current_performance_revisions',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                throw new LogicException("Periodic reporting lineage requires table: {$table}");
            }
        }
    }
}