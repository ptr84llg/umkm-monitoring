<?php

namespace App\Services\AdminDinas;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDinasDashboardService
{
    public function build(array $filters, bool $canViewFinancial): array
    {
        $base = $this->baseQuery($filters);
        $totalUmkm = (clone $base)->count();

        return [
            'filters' => $filters,
            'filter_options' => $this->filterOptions(),
            'summary' => [
                'total_umkm' => $totalUmkm,
                'workforce_recorded' => $this->workforceRecorded($filters),
                'spatial_associated' => $this->spatialAssociated($filters),
                'spatial_unassociated' => max(0, $totalUmkm - $this->spatialAssociated($filters)),
                'quality_affected' => $this->qualityAffected($filters),
            ],
            'districts' => $this->districtSummary($filters),
            'categories' => $this->categorySummary($filters),
            'freshness' => $this->freshness(),
            'can_view_financial' => $canViewFinancial,
            'financial' => $canViewFinancial ? $this->financialAnalytics($filters, $totalUmkm) : null,
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        $query = DB::table('umkms')
            ->whereIn('umkms.status_data', $this->operationalStatuses());

        if (Schema::hasColumn('umkms', 'deleted_at')) {
            $query->whereNull('umkms.deleted_at');
        }

        if (
            Schema::hasColumn('umkms', 'source_system')
            && Schema::hasColumn('umkms', 'source_active')
        ) {
            $query->where(function (Builder $guard): void {
                $guard->whereNull('umkms.source_system')
                    ->orWhere('umkms.source_system', '<>', 'LSS')
                    ->orWhere('umkms.source_active', 1);
            });
        }

        if (! empty($filters['quality_status'])) {
            $query->where('umkms.quality_status', (string) $filters['quality_status']);
        }

        if (! empty($filters['district_id']) && Schema::hasTable('umkm_locations')) {
            $districtId = (int) $filters['district_id'];

            $query->whereExists(function (Builder $sub) use ($districtId): void {
                $sub->selectRaw('1')
                    ->from('umkm_locations as filter_locations')
                    ->whereColumn('filter_locations.umkm_id', 'umkms.id')
                    ->where('filter_locations.district_region_id', $districtId);

                if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
                    $sub->whereNull('filter_locations.deleted_at');
                }
            });
        }

        if (! empty($filters['village_id']) && Schema::hasTable('umkm_locations')) {
            $villageId = (int) $filters['village_id'];

            $query->whereExists(function (Builder $sub) use ($villageId): void {
                $sub->selectRaw('1')
                    ->from('umkm_locations as filter_villages')
                    ->whereColumn('filter_villages.umkm_id', 'umkms.id')
                    ->where('filter_villages.village_region_id', $villageId);

                if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
                    $sub->whereNull('filter_villages.deleted_at');
                }
            });
        }

        if (
            ! empty($filters['category_id'])
            && Schema::hasTable('umkm_business_classifications')
        ) {
            $categoryId = (int) $filters['category_id'];

            $query->whereExists(function (Builder $sub) use ($categoryId): void {
                $sub->selectRaw('1')
                    ->from('umkm_business_classifications as filter_categories')
                    ->whereColumn('filter_categories.umkm_id', 'umkms.id')
                    ->where('filter_categories.business_category_id', $categoryId);
            });
        }

        if (
            ! empty($filters['type_id'])
            && Schema::hasTable('umkm_business_classifications')
        ) {
            $typeId = (int) $filters['type_id'];

            $query->whereExists(function (Builder $sub) use ($typeId): void {
                $sub->selectRaw('1')
                    ->from('umkm_business_classifications as filter_types')
                    ->whereColumn('filter_types.umkm_id', 'umkms.id')
                    ->where('filter_types.business_type_id', $typeId);
            });
        }

        if (
            ! empty($filters['marketing_method_id'])
            && Schema::hasTable('umkm_baseline_profiles')
        ) {
            $marketingMethodId = (int) $filters['marketing_method_id'];

            $query->whereExists(function (Builder $sub) use ($marketingMethodId): void {
                $sub->selectRaw('1')
                    ->from('umkm_baseline_profiles as filter_baseline')
                    ->whereColumn('filter_baseline.umkm_id', 'umkms.id')
                    ->where('filter_baseline.marketing_method_id', $marketingMethodId);
            });
        }

        return $query;
    }

    private function filterOptions(): array
    {
        $districts = collect();
        $villages = collect();
        $categories = collect();
        $types = collect();
        $marketingMethods = collect();
        $qualityStatuses = collect();

        if (Schema::hasTable('regions') && Schema::hasTable('umkm_locations')) {
            $districts = DB::table('regions as r')
                ->join('umkm_locations as l', 'l.district_region_id', '=', 'r.id')
                ->select('r.id', 'r.code', 'r.name')
                ->distinct()
                ->orderBy('r.name')
                ->get();

            $villages = DB::table('regions as r')
                ->join('umkm_locations as l', 'l.village_region_id', '=', 'r.id')
                ->select('r.id', 'r.code', 'r.name', 'r.parent_code')
                ->distinct()
                ->orderBy('r.name')
                ->get();
        }

        if (Schema::hasTable('business_category_references')) {
            $categories = DB::table('business_category_references')
                ->when(
                    Schema::hasColumn('business_category_references', 'is_active'),
                    fn (Builder $q) => $q->where('is_active', 1)
                )
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if (Schema::hasTable('business_type_references')) {
            $types = DB::table('business_type_references')
                ->when(
                    Schema::hasColumn('business_type_references', 'is_active'),
                    fn (Builder $q) => $q->where('is_active', 1)
                )
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if (Schema::hasTable('marketing_method_references')) {
            $marketingMethods = DB::table('marketing_method_references')
                ->when(
                    Schema::hasColumn('marketing_method_references', 'is_active'),
                    fn (Builder $q) => $q->where('is_active', 1)
                )
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if (Schema::hasColumn('umkms', 'quality_status')) {
            $qualityStatuses = DB::table('umkms')
                ->whereNotNull('quality_status')
                ->whereRaw("TRIM(quality_status) <> ''")
                ->distinct()
                ->orderBy('quality_status')
                ->pluck('quality_status');
        }

        return compact('districts', 'villages', 'categories', 'types', 'marketingMethods', 'qualityStatuses');
    }

    private function workforceRecorded(array $filters): int
    {
        if (
            ! Schema::hasTable('umkm_baseline_profiles')
            || ! Schema::hasColumn('umkm_baseline_profiles', 'employee_count')
        ) {
            return 0;
        }

        return (int) DB::table('umkm_baseline_profiles as b')
            ->whereIn('b.umkm_id', $this->baseQuery($filters)->select('umkms.id'))
            ->whereNotNull('b.employee_count')
            ->sum('b.employee_count');
    }

    private function spatialAssociated(array $filters): int
    {
        if (! Schema::hasTable('umkm_locations')) {
            return 0;
        }

        return (int) DB::table('umkm_locations as l')
            ->whereIn('l.umkm_id', $this->baseQuery($filters)->select('umkms.id'))
            ->whereNotNull('l.district_region_id')
            ->distinct()
            ->count('l.umkm_id');
    }

    private function qualityAffected(array $filters): int
    {
        if (! Schema::hasTable('umkm_data_quality_flags')) {
            return 0;
        }

        $query = DB::table('umkm_data_quality_flags as q')
            ->whereIn('q.umkm_id', $this->baseQuery($filters)->select('umkms.id'));

        if (Schema::hasColumn('umkm_data_quality_flags', 'status')) {
            $query->where('q.status', 'open');
        }

        return (int) $query->distinct()->count('q.umkm_id');
    }

    private function districtSummary(array $filters): array
    {
        if (! Schema::hasTable('umkm_locations') || ! Schema::hasTable('regions')) {
            return [];
        }

        $query = DB::table('umkm_locations as l')
            ->join('regions as r', 'r.id', '=', 'l.district_region_id')
            ->whereIn('l.umkm_id', $this->baseQuery($filters)->select('umkms.id'));

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query
            ->selectRaw('r.id, r.code, r.name, COUNT(DISTINCT l.umkm_id) AS total_umkm')
            ->groupBy('r.id', 'r.code', 'r.name')
            ->orderByDesc('total_umkm')
            ->orderBy('r.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => (string) $row->code,
                'name' => (string) $row->name,
                'total_umkm' => (int) $row->total_umkm,
            ])
            ->all();
    }

    private function categorySummary(array $filters): array
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_category_references')
        ) {
            return [];
        }

        return DB::table('umkm_business_classifications as c')
            ->join('business_category_references as r', 'r.id', '=', 'c.business_category_id')
            ->whereIn('c.umkm_id', $this->baseQuery($filters)->select('umkms.id'))
            ->selectRaw('r.id, r.name, COUNT(DISTINCT c.umkm_id) AS total_umkm')
            ->groupBy('r.id', 'r.name')
            ->orderByDesc('total_umkm')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'total_umkm' => (int) $row->total_umkm,
            ])
            ->all();
    }

    private function freshness(): array
    {
        if (! Schema::hasTable('lss_sync_runs')) {
            return [
                'snapshot_id' => null,
                'completed_at' => null,
                'source_system' => 'LSS',
            ];
        }

        $query = DB::table('lss_sync_runs');

        if (Schema::hasColumn('lss_sync_runs', 'source_system')) {
            $query->where('source_system', 'LSS');
        }

        if (Schema::hasColumn('lss_sync_runs', 'status')) {
            $query->where('status', 'completed');
        }

        $row = $query->orderByDesc('completed_at')->first();

        return [
            'snapshot_id' => $row->snapshot_id ?? null,
            'completed_at' => $row->completed_at ?? null,
            'source_system' => $row->source_system ?? 'LSS',
        ];
    }

    private function financialAnalytics(array $filters, int $totalUmkm): array
    {
        if (! Schema::hasTable('umkm_baseline_profiles')) {
            return [
                'coverage' => $this->emptyFinancialCoverage($totalUmkm),
                'districts' => [],
                'loan_sources' => [],
                'details' => [],
            ];
        }

        $baseIds = $this->baseQuery($filters)->select('umkms.id');

        $coverage = [
            'total_umkm' => $totalUmkm,
            'capital_filled' => $this->filledCount($filters, 'capital_amount'),
            'annual_sales_filled' => $this->filledCount($filters, 'annual_sales_amount'),
            'monthly_revenue_filled' => $this->filledCount($filters, 'baseline_monthly_revenue'),
            'loan_amount_filled' => $this->filledCount($filters, 'loan_amount'),
            'loan_source_filled' => $this->filledTextCount($filters, 'loan_source'),
        ];

        $districts = [];

        if (Schema::hasTable('umkm_locations') && Schema::hasTable('regions')) {
            $districtQuery = DB::table('umkm_locations as l')
                ->join('regions as r', 'r.id', '=', 'l.district_region_id')
                ->leftJoin('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'l.umkm_id')
                ->whereIn('l.umkm_id', $this->baseQuery($filters)->select('umkms.id'));

            $districts = $districtQuery
                ->selectRaw(
                    'r.id, r.name,
                    COUNT(DISTINCT l.umkm_id) AS total_umkm,
                    COUNT(DISTINCT CASE WHEN b.capital_amount IS NOT NULL THEN l.umkm_id END) AS capital_filled,
                    COUNT(DISTINCT CASE WHEN b.annual_sales_amount IS NOT NULL THEN l.umkm_id END) AS annual_sales_filled,
                    COUNT(DISTINCT CASE WHEN b.loan_amount IS NOT NULL THEN l.umkm_id END) AS loan_amount_filled,
                    COUNT(DISTINCT CASE WHEN b.loan_source IS NOT NULL AND TRIM(b.loan_source) <> "" THEN l.umkm_id END) AS loan_source_filled'
                )
                ->groupBy('r.id', 'r.name')
                ->orderByDesc('total_umkm')
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'total_umkm' => (int) $row->total_umkm,
                    'capital_filled' => (int) $row->capital_filled,
                    'annual_sales_filled' => (int) $row->annual_sales_filled,
                    'loan_amount_filled' => (int) $row->loan_amount_filled,
                    'loan_source_filled' => (int) $row->loan_source_filled,
                ])
                ->all();
        }

        $loanSources = [];

        if (Schema::hasColumn('umkm_baseline_profiles', 'loan_source')) {
            $loanSources = DB::table('umkm_baseline_profiles as b')
                ->whereIn('b.umkm_id', $this->baseQuery($filters)->select('umkms.id'))
                ->selectRaw(
                    "COALESCE(NULLIF(TRIM(b.loan_source), ''), 'Belum tersedia') AS source_name,
                    COUNT(DISTINCT b.umkm_id) AS total_umkm"
                )
                ->groupByRaw("COALESCE(NULLIF(TRIM(b.loan_source), ''), 'Belum tersedia')")
                ->orderByDesc('total_umkm')
                ->limit(15)
                ->get()
                ->map(fn ($row) => [
                    'name' => (string) $row->source_name,
                    'total_umkm' => (int) $row->total_umkm,
                ])
                ->all();
        }

        $details = DB::table('umkms as u')
            ->leftJoin('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'u.id')
            ->leftJoin('umkm_locations as l', 'l.umkm_id', '=', 'u.id')
            ->leftJoin('regions as d', 'd.id', '=', 'l.district_region_id')
            ->whereIn('u.id', $baseIds)
            ->select([
                'u.id',
                'u.umkm_code',
                'u.business_name',
                'u.quality_status',
                'd.name as district_name',
                'b.capital_amount',
                'b.annual_sales_amount',
                'b.baseline_monthly_revenue',
                'b.loan_amount',
                'b.loan_source',
                'b.lss_detail_synced_at',
            ])
            ->orderBy('u.business_name')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'umkm_code' => (string) $row->umkm_code,
                'business_name' => (string) $row->business_name,
                'quality_status' => $row->quality_status,
                'district_name' => $row->district_name,
                'capital_amount' => $row->capital_amount,
                'annual_sales_amount' => $row->annual_sales_amount,
                'baseline_monthly_revenue' => $row->baseline_monthly_revenue,
                'loan_amount' => $row->loan_amount,
                'loan_source' => $row->loan_source,
                'lss_detail_synced_at' => $row->lss_detail_synced_at,
            ])
            ->all();

        return compact('coverage', 'districts', 'loanSources', 'details');
    }

    private function filledCount(array $filters, string $column): int
    {
        if (! Schema::hasColumn('umkm_baseline_profiles', $column)) {
            return 0;
        }

        return (int) DB::table('umkm_baseline_profiles as b')
            ->whereIn('b.umkm_id', $this->baseQuery($filters)->select('umkms.id'))
            ->whereNotNull('b.' . $column)
            ->distinct()
            ->count('b.umkm_id');
    }

    private function filledTextCount(array $filters, string $column): int
    {
        if (! Schema::hasColumn('umkm_baseline_profiles', $column)) {
            return 0;
        }

        return (int) DB::table('umkm_baseline_profiles as b')
            ->whereIn('b.umkm_id', $this->baseQuery($filters)->select('umkms.id'))
            ->whereNotNull('b.' . $column)
            ->whereRaw('TRIM(b.' . $column . ") <> ''")
            ->distinct()
            ->count('b.umkm_id');
    }

    private function emptyFinancialCoverage(int $totalUmkm): array
    {
        return [
            'total_umkm' => $totalUmkm,
            'capital_filled' => 0,
            'annual_sales_filled' => 0,
            'monthly_revenue_filled' => 0,
            'loan_amount_filled' => 0,
            'loan_source_filled' => 0,
        ];
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