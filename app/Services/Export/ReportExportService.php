<?php

namespace App\Services\Export;

use App\Models\Audit\ExportLog;
use App\Models\Reference\Region;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmBusinessClassification;
use App\Models\Umkm\UmkmLegality;
use App\Models\Umkm\UmkmPerformanceRecord;
use App\Models\Validation\ExpertValidationAssessment;
use App\Models\Validation\SurveyResponse;
use Illuminate\Support\Facades\Schema;

class ReportExportService
{
    public function reports(array $filters = []): array
    {
        return [
            'umkm_ringkas' => $this->umkmSummary(),
            'legalitas_status' => UmkmLegality::query()
                ->selectRaw('status_data, COUNT(*) total')
                ->groupBy('status_data')
                ->get()
                ->toArray(),
            'klasifikasi_usaha' => $this->businessClassificationSummary(),
            'wilayah' => Region::query()
                ->select(['level'])
                ->selectRaw('COUNT(*) total')
                ->groupBy('level')
                ->get()
                ->toArray(),
            'kinerja_periodik' => UmkmPerformanceRecord::query()
                ->selectRaw('monitoring_period_id, COUNT(*) total, AVG(monthly_revenue) avg_revenue')
                ->groupBy('monitoring_period_id')
                ->get()
                ->toArray(),
            'survei' => SurveyResponse::query()
                ->selectRaw('status, COUNT(*) total')
                ->groupBy('status')
                ->get()
                ->toArray(),
            'validasi_ahli' => ExpertValidationAssessment::query()
                ->selectRaw('status, COUNT(*) total, AVG(score) avg_score')
                ->groupBy('status')
                ->get()
                ->toArray(),
        ];
    }

    public function applyMasking(array $data, bool $allowSensitive): array
    {
        if ($allowSensitive) {
            return $data;
        }

        foreach ($data['umkm_ringkas'] ?? [] as &$row) {
            $row['business_name'] = mb_substr((string) $row['business_name'], 0, 3) . '***';
        }

        return $data;
    }

    public function watermark(array $meta): string
    {
        return sprintf(
            'WATERMARK|role:%s|user:%s|at:%s|reason:%s',
            $meta['role'],
            $meta['user_id'],
            now()->toIso8601String(),
            $meta['reason']
        );
    }

    public function logExport(array $payload): ExportLog
    {
        return ExportLog::query()->create($payload);
    }

    private function umkmSummary(): array
    {
        return Umkm::query()
            ->whereIn('status_data', $this->operationalStatuses())
            ->select(['umkm_code', 'business_name', 'status_data', 'quality_status'])
            ->limit(100)
            ->get()
            ->toArray();
    }

    private function businessClassificationSummary(): array
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_category_references')
            || ! Schema::hasTable('business_type_references')
        ) {
            return [];
        }

        return UmkmBusinessClassification::query()
            ->join('business_category_references as categories', 'categories.id', '=', 'umkm_business_classifications.business_category_id')
            ->join('business_type_references as types', 'types.id', '=', 'umkm_business_classifications.business_type_id')
            ->selectRaw('categories.name as category_name, types.name as type_name, COUNT(DISTINCT umkm_business_classifications.umkm_id) total')
            ->groupBy('categories.name', 'types.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
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
