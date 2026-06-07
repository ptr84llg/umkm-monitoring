<?php

namespace App\Services\KepalaDinas;

use App\Models\Audit\AuditLog;
use App\Models\Audit\ExportLog;
use App\Models\General\MonitoringPeriod;
use App\Models\Reference\BusinessCategoryReference;
use App\Models\Reference\BusinessTypeReference;
use App\Models\Reference\Region;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmLegality;
use App\Models\Umkm\UmkmPerformanceRecord;

class StrategicDashboardService
{
    public function indicators(): array
    {
        return [
            'total_umkm' => Umkm::query()->whereIn('status_data', $this->operationalStatuses())->count(),
            'legalitas_terisi' => UmkmLegality::query()->whereNotNull('nib_number')->count(),
            'kategori_usaha_aktif' => BusinessCategoryReference::query()->where('is_active', true)->count(),
            'jenis_usaha_aktif' => BusinessTypeReference::query()->where('is_active', true)->count(),
            'wilayah_aktif' => Region::query()->where('is_active', true)->count(),
            'periode_monitoring' => MonitoringPeriod::query()->count(),
            'rekam_kinerja' => UmkmPerformanceRecord::query()->count(),
        ];
    }

    public function aggregateMapPoints(): array
    {
        return Umkm::query()
            ->selectRaw('status_data, COUNT(*) as total')
            ->whereIn('status_data', $this->operationalStatuses())
            ->groupBy('status_data')
            ->pluck('total', 'status_data')
            ->toArray();
    }

    public function latestAuditSummary(): array
    {
        return AuditLog::query()
            ->latest()
            ->limit(20)
            ->get(['action', 'target_type', 'event_time'])
            ->toArray();
    }

    public function recordExport(int $actorId, string $reason): void
    {
        ExportLog::query()->create([
            'actor_user_id' => $actorId,
            'export_type' => 'kepala_dinas_ringkasan',
            'export_reason' => $reason,
            'watermark_token' => sha1($actorId . '|' . $reason . '|' . now()->toIso8601String()),
            'status' => 'requested',
            'exported_at' => now(),
        ]);
    }

    private function operationalStatuses(): array
    {
        $statuses = array_values(array_filter(array_map(
            fn ($status) => trim((string) $status),
            (array) config('umkm.data.operational_statuses', ['resmi', 'terbatas'])
        )));

        return $statuses === [] ? ['resmi', 'terbatas'] : $statuses;
    }
}
