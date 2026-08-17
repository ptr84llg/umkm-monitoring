<?php

namespace App\Services\AdminDinas;

use App\Support\PublicLanding\PublicLandingDataFreshness;
use App\Support\PublicLanding\PublicLandingRegionGeometry;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDinasSpatialAnalyticsService
{
    public function __construct(
        private AdminDinasDashboardService $dashboardService
    ) {
    }

    public function build(
        array $filters,
        bool $canFinancial,
        bool $canCoordinate
    ): array {
        $base = $this->baseQuery($filters);
        $total = (int) (clone $base)->distinct()->count('u.id');
        $administrativeAssociated = $this->administrativeAssociated($filters);
        $coordinateMapped = $this->coordinateMapped($filters);

        $geometryInput = $this->geometryInput($filters);
        $geometryPayload = PublicLandingRegionGeometry::payload($geometryInput);
        $visibleLevel = (string) ($geometryPayload['selection']['visible_level'] ?? 'district');

        $features = (array) ($geometryPayload['geometry']['features'] ?? []);
        $regionCodes = collect($features)
            ->map(fn (array $feature): string => trim((string) ($feature['properties']['region_code'] ?? '')))
            ->filter(fn (string $code): bool => $code !== '')
            ->unique()
            ->values()
            ->all();

        $regionRows = $this->regionMetrics(
            $filters,
            $visibleLevel,
            $regionCodes,
            $canFinancial
        );

        $regionLookup = collect($regionRows)->keyBy('code');

        $features = collect($features)
            ->map(function (array $feature) use ($regionLookup, $filters, $visibleLevel): array {
                $code = trim((string) ($feature['properties']['region_code'] ?? ''));
                $row = $regionLookup->get($code);

                $metrics = [
                    'region_id' => $row['id'] ?? null,
                    'umkm_total' => (int) ($row['total_umkm'] ?? 0),
                    'workers_total' => (int) ($row['workers_total'] ?? 0),
                    'quality_affected' => (int) ($row['quality_affected'] ?? 0),
                    'financial_filled' => (int) ($row['financial_filled'] ?? 0),
                    'dominant_category' => $row['dominant_category'] ?? null,
                ];

                $params = array_filter(
                    $filters,
                    static fn ($value) => $value !== null && $value !== ''
                );

                if (($metrics['region_id'] ?? null) !== null) {
                    if ($visibleLevel === 'village') {
                        $params['village_id'] = $metrics['region_id'];
                    } else {
                        $params['district_id'] = $metrics['region_id'];
                    }
                }

                $feature['properties'] = array_merge(
                    $feature['properties'] ?? [],
                    $metrics,
                    [
                        'data_url' => route('admin-dinas.umkm.index', $params),
                    ]
                );

                return $feature;
            })
            ->values()
            ->all();

        $points = $canCoordinate
            ? $this->coordinatePoints($filters)
            : [];

        return [
            'filters' => $filters,
            'filter_options' => $this->filterOptions(),
            'summary' => [
                'total_umkm' => $total,
                'administrative_associated' => $administrativeAssociated,
                'administrative_unassociated' => max(0, $total - $administrativeAssociated),
                'coordinate_mapped' => $coordinateMapped,
                'coordinate_unmapped' => max(0, $total - $coordinateMapped),
                'coordinate_mapped_percent' => $this->percent($coordinateMapped, $total),
            ],
            'map' => [
                'scope' => $geometryPayload['scope'] ?? 'city',
                'selection' => $geometryPayload['selection'] ?? [],
                'geometry' => [
                    'type' => 'FeatureCollection',
                    'features' => $features,
                ],
                'points' => $points,
                'coordinate_access' => $canCoordinate,
                'visible_level' => $visibleLevel,
            ],
            'region_rows' => $regionRows,
            'can_view_financial' => $canFinancial,
            'can_view_coordinates' => $canCoordinate,
            'freshness' => PublicLandingDataFreshness::latest(),
            'coordinate_rule' => [
                'status' => 'terpetakan',
                'latitude_required' => true,
                'longitude_required' => true,
            ],
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        $query = DB::table('umkms as u')
            ->whereIn('u.status_data', $this->operationalStatuses());

        if (Schema::hasColumn('umkms', 'deleted_at')) {
            $query->whereNull('u.deleted_at');
        }

        if (
            Schema::hasColumn('umkms', 'source_system')
            && Schema::hasColumn('umkms', 'source_active')
        ) {
            $query->where(function (Builder $guard): void {
                $guard->whereNull('u.source_system')
                    ->orWhere('u.source_system', '<>', 'LSS')
                    ->orWhere('u.source_active', 1);
            });
        }

        if (
            ! empty($filters['quality_status'])
            && Schema::hasColumn('umkms', 'quality_status')
        ) {
            $query->where('u.quality_status', (string) $filters['quality_status']);
        }

        $this->applyExistsFilter(
            $query,
            $filters,
            'district_id',
            'umkm_locations',
            'district_region_id',
            'sd'
        );

        $this->applyExistsFilter(
            $query,
            $filters,
            'village_id',
            'umkm_locations',
            'village_region_id',
            'sv'
        );

        $this->applyExistsFilter(
            $query,
            $filters,
            'category_id',
            'umkm_business_classifications',
            'business_category_id',
            'sc'
        );

        $this->applyExistsFilter(
            $query,
            $filters,
            'type_id',
            'umkm_business_classifications',
            'business_type_id',
            'st'
        );

        $this->applyExistsFilter(
            $query,
            $filters,
            'marketing_method_id',
            'umkm_baseline_profiles',
            'marketing_method_id',
            'sm'
        );

        return $query;
    }

    private function applyExistsFilter(
        Builder $query,
        array $filters,
        string $filterKey,
        string $table,
        string $column,
        string $alias
    ): void {
        if (empty($filters[$filterKey]) || ! Schema::hasTable($table)) {
            return;
        }

        $value = (int) $filters[$filterKey];

        $query->whereExists(function (Builder $sub) use (
            $value,
            $table,
            $column,
            $alias
        ): void {
            $sub->selectRaw('1')
                ->from($table . ' as ' . $alias)
                ->whereColumn($alias . '.umkm_id', 'u.id')
                ->where($alias . '.' . $column, $value);

            if (
                $table === 'umkm_locations'
                && Schema::hasColumn('umkm_locations', 'deleted_at')
            ) {
                $sub->whereNull($alias . '.deleted_at');
            }
        });
    }

    private function baseIds(array $filters): Builder
    {
        return $this->baseQuery($filters)->select('u.id');
    }

    private function administrativeAssociated(array $filters): int
    {
        if (! Schema::hasTable('umkm_locations')) {
            return 0;
        }

        $query = DB::table('umkm_locations as l')
            ->whereIn('l.umkm_id', $this->baseIds($filters))
            ->whereNotNull('l.district_region_id');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return (int) $query->distinct()->count('l.umkm_id');
    }

    private function coordinateMapped(array $filters): int
    {
        if (! $this->coordinateColumnsReady()) {
            return 0;
        }

        $query = DB::table('umkm_locations as l')
            ->whereIn('l.umkm_id', $this->baseIds($filters))
            ->where('l.coordinate_status', 'terpetakan')
            ->whereNotNull('l.latitude')
            ->whereNotNull('l.longitude');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return (int) $query->distinct()->count('l.umkm_id');
    }

    private function coordinatePoints(array $filters): array
    {
        if (! $this->coordinateColumnsReady()) {
            return [];
        }

        $query = DB::table('umkm_locations as l')
            ->join('umkms as u', 'u.id', '=', 'l.umkm_id')
            ->leftJoin('regions as d', 'd.id', '=', 'l.district_region_id')
            ->leftJoin('regions as v', 'v.id', '=', 'l.village_region_id')
            ->whereIn('u.id', $this->baseIds($filters))
            ->where('l.coordinate_status', 'terpetakan')
            ->whereNotNull('l.latitude')
            ->whereNotNull('l.longitude')
            ->select([
                'u.id',
                'u.umkm_code',
                'u.business_name',
                'l.latitude',
                'l.longitude',
                'd.name as district_name',
                'v.name as village_name',
            ])
            ->orderBy('u.id')
            ->orderBy('l.id');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query
            ->get()
            ->unique('id')
            ->values()
            ->map(fn ($row) => [
                'umkm_id' => (int) $row->id,
                'umkm_code' => (string) $row->umkm_code,
                'business_name' => (string) $row->business_name,
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'district_name' => $row->district_name,
                'village_name' => $row->village_name,
                'detail_url' => route('admin-dinas.umkm.show', $row->id),
            ])
            ->all();
    }

    private function regionMetrics(
        array $filters,
        string $level,
        array $codes,
        bool $canFinancial
    ): array {
        if (
            $codes === []
            || ! Schema::hasTable('regions')
            || ! Schema::hasTable('umkm_locations')
        ) {
            return [];
        }

        $level = $level === 'village' ? 'village' : 'district';
        $regionColumn = $level . '_region_id';

        if (! Schema::hasColumn('umkm_locations', $regionColumn)) {
            return [];
        }

        $regions = DB::table('regions')
            ->whereIn('code', $codes)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->keyBy('code');

        $locationSubquery = DB::table('umkm_locations')
            ->selectRaw('umkm_id, MIN(' . $regionColumn . ') AS region_id')
            ->whereNotNull($regionColumn)
            ->groupBy('umkm_id');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $locationSubquery->whereNull('deleted_at');
        }

        $baseIds = $this->baseIds($filters);

        $totals = DB::query()
            ->fromSub(clone $locationSubquery, 'lr')
            ->join('regions as r', 'r.id', '=', 'lr.region_id')
            ->whereIn('lr.umkm_id', clone $baseIds)
            ->whereIn('r.code', $codes)
            ->selectRaw('r.code, COUNT(DISTINCT lr.umkm_id) AS total_count')
            ->groupBy('r.code')
            ->pluck('total_count', 'code')
            ->map(fn ($value) => (int) $value);

        $workers = collect();

        if (
            Schema::hasTable('umkm_baseline_profiles')
            && Schema::hasColumn('umkm_baseline_profiles', 'employee_count')
        ) {
            $workers = DB::query()
                ->fromSub(clone $locationSubquery, 'lr')
                ->join('regions as r', 'r.id', '=', 'lr.region_id')
                ->join('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'lr.umkm_id')
                ->whereIn('lr.umkm_id', clone $baseIds)
                ->whereIn('r.code', $codes)
                ->selectRaw(
                    'r.code, COALESCE(SUM(b.employee_count), 0) AS workers_total'
                )
                ->groupBy('r.code')
                ->pluck('workers_total', 'code')
                ->map(fn ($value) => (int) $value);
        }

        $quality = collect();

        if (Schema::hasTable('umkm_data_quality_flags')) {
            $qualityQuery = DB::query()
                ->fromSub(clone $locationSubquery, 'lr')
                ->join('regions as r', 'r.id', '=', 'lr.region_id')
                ->join(
                    'umkm_data_quality_flags as q',
                    'q.umkm_id',
                    '=',
                    'lr.umkm_id'
                )
                ->whereIn('lr.umkm_id', clone $baseIds)
                ->whereIn('r.code', $codes);

            if (Schema::hasColumn('umkm_data_quality_flags', 'status')) {
                $qualityQuery->where('q.status', 'open');
            }

            $quality = $qualityQuery
                ->selectRaw(
                    'r.code, COUNT(DISTINCT lr.umkm_id) AS affected_count'
                )
                ->groupBy('r.code')
                ->pluck('affected_count', 'code')
                ->map(fn ($value) => (int) $value);
        }

        $financial = collect();

        if ($canFinancial && Schema::hasTable('umkm_baseline_profiles')) {
            $financialColumns = array_values(array_filter([
                Schema::hasColumn('umkm_baseline_profiles', 'capital_amount')
                    ? 'capital_amount'
                    : null,
                Schema::hasColumn('umkm_baseline_profiles', 'annual_sales_amount')
                    ? 'annual_sales_amount'
                    : null,
                Schema::hasColumn('umkm_baseline_profiles', 'baseline_monthly_revenue')
                    ? 'baseline_monthly_revenue'
                    : null,
                Schema::hasColumn('umkm_baseline_profiles', 'loan_amount')
                    ? 'loan_amount'
                    : null,
                Schema::hasColumn('umkm_baseline_profiles', 'loan_source')
                    ? 'loan_source'
                    : null,
            ]));

            if ($financialColumns !== []) {
                $financialQuery = DB::query()
                    ->fromSub(clone $locationSubquery, 'lr')
                    ->join('regions as r', 'r.id', '=', 'lr.region_id')
                    ->join(
                        'umkm_baseline_profiles as b',
                        'b.umkm_id',
                        '=',
                        'lr.umkm_id'
                    )
                    ->whereIn('lr.umkm_id', clone $baseIds)
                    ->whereIn('r.code', $codes)
                    ->where(function (Builder $filled) use ($financialColumns): void {
                        foreach ($financialColumns as $index => $column) {
                            if ($index === 0) {
                                $filled->whereNotNull('b.' . $column);
                            } else {
                                $filled->orWhereNotNull('b.' . $column);
                            }
                        }
                    });

                $financial = $financialQuery
                    ->selectRaw(
                        'r.code, COUNT(DISTINCT lr.umkm_id) AS filled_count'
                    )
                    ->groupBy('r.code')
                    ->pluck('filled_count', 'code')
                    ->map(fn ($value) => (int) $value);
            }
        }

        $dominant = [];

        if (
            Schema::hasTable('umkm_business_classifications')
            && Schema::hasTable('business_category_references')
        ) {
            $categoryRows = DB::query()
                ->fromSub(clone $locationSubquery, 'lr')
                ->join('regions as r', 'r.id', '=', 'lr.region_id')
                ->join(
                    'umkm_business_classifications as c',
                    'c.umkm_id',
                    '=',
                    'lr.umkm_id'
                )
                ->join(
                    'business_category_references as bc',
                    'bc.id',
                    '=',
                    'c.business_category_id'
                )
                ->whereIn('lr.umkm_id', clone $baseIds)
                ->whereIn('r.code', $codes)
                ->selectRaw(
                    'r.code, bc.name, COUNT(DISTINCT lr.umkm_id) AS category_total'
                )
                ->groupBy('r.code', 'bc.name')
                ->orderBy('r.code')
                ->orderByDesc('category_total')
                ->get();

            foreach ($categoryRows as $row) {
                $code = (string) $row->code;

                if (! isset($dominant[$code])) {
                    $dominant[$code] = [
                        'name' => (string) $row->name,
                        'total' => (int) $row->category_total,
                    ];
                }
            }
        }

        return collect($codes)
            ->map(function (string $code) use (
                $regions,
                $totals,
                $workers,
                $quality,
                $financial,
                $dominant,
                $level
            ): ?array {
                $region = $regions->get($code);

                if (! $region) {
                    return null;
                }

                return [
                    'id' => (int) $region->id,
                    'code' => (string) $region->code,
                    'name' => (string) $region->name,
                    'level' => $level,
                    'total_umkm' => (int) ($totals[$code] ?? 0),
                    'workers_total' => (int) ($workers[$code] ?? 0),
                    'quality_affected' => (int) ($quality[$code] ?? 0),
                    'financial_filled' => (int) ($financial[$code] ?? 0),
                    'dominant_category' => $dominant[$code]['name'] ?? null,
                    'dominant_category_total' => (int) ($dominant[$code]['total'] ?? 0),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function geometryInput(array $filters): array
    {
        $district = null;
        $village = null;

        if (
            ! empty($filters['district_id'])
            && Schema::hasTable('regions')
        ) {
            $district = DB::table('regions')
                ->where('id', (int) $filters['district_id'])
                ->first(['id', 'code', 'name', 'parent_code']);
        }

        if (
            ! empty($filters['village_id'])
            && Schema::hasTable('regions')
        ) {
            $village = DB::table('regions')
                ->where('id', (int) $filters['village_id'])
                ->first(['id', 'code', 'name', 'parent_code']);

            if ($village && ! $district && $village->parent_code) {
                $district = DB::table('regions')
                    ->where('code', $village->parent_code)
                    ->first(['id', 'code', 'name', 'parent_code']);
            }
        }

        if ($village) {
            return [
                'scope' => 'village',
                'district_code' => (string) ($district->code ?? ''),
                'district_name' => (string) ($district->name ?? ''),
                'village_code' => (string) $village->code,
                'village_name' => (string) $village->name,
            ];
        }

        if ($district) {
            return [
                'scope' => 'district',
                'district_code' => (string) $district->code,
                'district_name' => (string) $district->name,
            ];
        }

        return [
            'scope' => 'city',
        ];
    }

    private function filterOptions(): array
    {
        $dashboard = $this->dashboardService->build([], false);
        $options = $dashboard['filter_options'] ?? [];

        $options['qualityStatuses'] = Schema::hasTable('umkms')
            && Schema::hasColumn('umkms', 'quality_status')
            ? DB::table('umkms')
                ->whereNotNull('quality_status')
                ->whereRaw("TRIM(quality_status) <> ''")
                ->distinct()
                ->orderBy('quality_status')
                ->pluck('quality_status')
            : collect();

        return $options;
    }

    private function coordinateColumnsReady(): bool
    {
        return Schema::hasTable('umkm_locations')
            && Schema::hasColumn('umkm_locations', 'coordinate_status')
            && Schema::hasColumn('umkm_locations', 'latitude')
            && Schema::hasColumn('umkm_locations', 'longitude');
    }

    private function percent(int $value, int $total): float
    {
        if ($total < 1) {
            return 0.0;
        }

        return round(($value / $total) * 100, 2);
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
