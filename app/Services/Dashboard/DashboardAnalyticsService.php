<?php

namespace App\Services\Dashboard;

use App\Models\Reference\Region;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmBusinessClassification;
use App\Models\Umkm\UmkmLegality;
use App\Models\Umkm\UmkmPerformanceRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class DashboardAnalyticsService
{
    private int $minAggregate = 3;

    public function indicators(array $filters = []): array
    {
        $query = $this->officialQuery($filters);
        $ids = (clone $query)->pluck('id');

        return [
            'total_umkm_operasional' => (clone $query)->count(),
            'legalitas_terisi' => UmkmLegality::query()
                ->whereIn('umkm_id', $ids)
                ->whereNotNull('nib_number')
                ->count(),
            'kinerja_records' => UmkmPerformanceRecord::query()
                ->whereIn('umkm_id', $ids)
                ->count(),
        ];
    }

    public function businessClassificationComposition(array $filters = []): array
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_category_references')
            || ! Schema::hasTable('business_type_references')
        ) {
            return [];
        }

        $ids = $this->officialQuery($filters)->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        return UmkmBusinessClassification::query()
            ->join('business_category_references as categories', 'categories.id', '=', 'umkm_business_classifications.business_category_id')
            ->join('business_type_references as types', 'types.id', '=', 'umkm_business_classifications.business_type_id')
            ->whereIn('umkm_business_classifications.umkm_id', $ids)
            ->selectRaw('categories.name as category_name, types.name as type_name, COUNT(DISTINCT umkm_business_classifications.umkm_id) total')
            ->groupBy('categories.name', 'types.name')
            ->havingRaw('COUNT(DISTINCT umkm_business_classifications.umkm_id) >= ?', [$this->minAggregate])
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function legalityStatus(array $filters = []): array
    {
        return UmkmLegality::query()
            ->selectRaw('status_data, COUNT(*) total')
            ->whereIn('umkm_id', $this->officialQuery($filters)->pluck('id'))
            ->groupBy('status_data')
            ->havingRaw('COUNT(*) >= ?', [$this->minAggregate])
            ->get()
            ->toArray();
    }

    public function performanceTrend(array $filters = []): array
    {
        return UmkmPerformanceRecord::query()
            ->selectRaw('monitoring_period_id, AVG(monthly_revenue) avg_revenue, COUNT(*) total')
            ->whereIn('umkm_id', $this->officialQuery($filters)->pluck('id'))
            ->groupBy('monitoring_period_id')
            ->havingRaw('COUNT(*) >= ?', [$this->minAggregate])
            ->get()
            ->toArray();
    }

    public function mapAggregate(array $filters = []): array
    {
        return Umkm::query()
            ->selectRaw('status_data, COUNT(*) total')
            ->whereIn('id', $this->officialQuery($filters)->pluck('id'))
            ->groupBy('status_data')
            ->havingRaw('COUNT(*) >= ?', [$this->minAggregate])
            ->get()
            ->toArray();
    }

    public function summaryTable(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->officialQuery($filters)
            ->select(['id', 'umkm_code', 'business_name', 'status_data', 'quality_status'])
            ->paginate($perPage);
    }

    private function officialQuery(array $filters = []): Builder
    {
        $query = Umkm::query()->whereIn('status_data', $this->operationalStatuses());

        if (! empty($filters['quality_status'])) {
            $query->where('quality_status', $filters['quality_status']);
        }

        if (! empty($filters['data_status'])) {
            $query->where('status_data', $filters['data_status']);
        }

        $this->applyRegionFilters($query, $filters);
        $this->applyBusinessClassificationFilters($query, $filters);

        return $query;
    }

    private function applyRegionFilters(Builder $query, array $filters): void
    {
        $regionColumnMap = [
            'province_code' => 'province_region_id',
            'city_code' => 'city_region_id',
            'district_code' => 'district_region_id',
            'village_code' => 'village_region_id',
        ];

        foreach ($regionColumnMap as $filterKey => $locationColumn) {
            if (empty($filters[$filterKey])) {
                continue;
            }

            $regionId = $this->regionIdByCode((string) $filters[$filterKey]);

            if ($regionId === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereHas('locations', fn (Builder $location) => $location->where($locationColumn, $regionId));
        }

        if (! empty($filters['region_id'])) {
            $regionId = (int) $filters['region_id'];

            $query->whereHas('locations', function (Builder $location) use ($regionId): void {
                $location->where('province_region_id', $regionId)
                    ->orWhere('city_region_id', $regionId)
                    ->orWhere('district_region_id', $regionId)
                    ->orWhere('village_region_id', $regionId);
            });
        }
    }

    private function applyBusinessClassificationFilters(Builder $query, array $filters): void
    {
        $categoryId = $filters['business_category_id'] ?? null;
        $typeId = $filters['business_type_id'] ?? null;

        if ($categoryId === null && $typeId === null) {
            return;
        }

        $query->whereHas('businessClassifications', function (Builder $classification) use ($categoryId, $typeId): void {
            if ($categoryId !== null) {
                $classification->where('business_category_id', (int) $categoryId);
            }

            if ($typeId !== null) {
                $classification->where('business_type_id', (int) $typeId);
            }
        });
    }

    private function regionIdByCode(string $code): ?int
    {
        if (! Schema::hasTable('regions')) {
            return null;
        }

        $id = Region::query()->where('code', $code)->value('id');

        return $id === null ? null : (int) $id;
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
