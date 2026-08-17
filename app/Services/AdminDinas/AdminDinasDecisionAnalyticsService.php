<?php

namespace App\Services\AdminDinas;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDinasDecisionAnalyticsService
{
    private const DEFAULT_MIN_GROUP_SIZE = 3;

    private const ECONOMIC_METRICS = [
        'baseline_monthly_revenue' => 'Omzet bulanan baseline',
        'annual_sales_amount' => 'Penjualan tahunan',
    ];


    public function __construct(
        private AdminDinasDashboardService $dashboardService
    ) {
    }

    public function build(
        array $filters,
        bool $canFinancial,
        bool $canCoordinate
    ): array {
        $minimumGroupSize = max(
            3,
            (int) config(
                'umkm.admin_dinas_decision_analytics.minimum_group_size',
                self::DEFAULT_MIN_GROUP_SIZE
            )
        );

        $analyticsFilters = $filters;
        $dashboard = $this->dashboardService->build($analyticsFilters, $canFinancial);
        $filterOptions = $dashboard['filter_options'] ?? [];
        $selectedType = $this->resolveOption(
            $filterOptions['types'] ?? [],
            $analyticsFilters['type_id'] ?? null
        );
        $selectedDistrict = $this->resolveOption(
            $filterOptions['districts'] ?? [],
            $analyticsFilters['district_id'] ?? null
        );

        $summary = [
            'total_umkm' => (int) data_get($dashboard, 'summary.total_umkm', 0),
            'type_count' => $this->contextTypeCount($analyticsFilters),
            'district_count' => $this->contextDistrictCount($analyticsFilters),
            'coordinate_mapped' => $this->coordinateMappedCount($analyticsFilters),
            'quality_affected' => (int) data_get($dashboard, 'summary.quality_affected', 0),
        ];
        $summary['coordinate_mapped_percent'] = $this->percent(
            $summary['coordinate_mapped'],
            $summary['total_umkm']
        );

        $competitionRows = [];
        $competitionSummary = null;

        if ($selectedType) {
            [$competitionRows, $competitionSummary] = $this->competitionByDistrict(
                $analyticsFilters,
                (int) $selectedType['id'],
                $canFinancial,
                $minimumGroupSize
            );
        }

        $opportunityRows = [];
        $opportunitySummary = null;

        if ($selectedDistrict) {
            [$opportunityRows, $opportunitySummary] = $this->opportunityTypes(
                $analyticsFilters,
                (int) $selectedDistrict['id'],
                $canFinancial,
                $minimumGroupSize
            );
        }

        $microSpatial = $this->microSpatial(
            $analyticsFilters,
            $selectedType ? (int) $selectedType['id'] : null,
            $canCoordinate
        );

        $qualityWarning = $summary['quality_affected'] > 0
            || collect($competitionRows)->contains(
                fn (array $row): bool => (bool) ($row['quality_warning'] ?? false)
            )
            || collect($opportunityRows)->contains(
                fn (array $row): bool => (bool) ($row['quality_warning'] ?? false)
            );

        $payload = [
            'filters' => $analyticsFilters,
            'filter_options' => $filterOptions,
            'selected_type' => $selectedType,
            'selected_district' => $selectedDistrict,
            'summary' => $summary,
            'competition_by_district' => $competitionRows,
            'competition_summary' => $competitionSummary,
            'opportunity_types' => $opportunityRows,
            'opportunity_summary' => $opportunitySummary,
            'micro_spatial' => $microSpatial,
            'quality_warning' => $qualityWarning,
            'freshness' => $dashboard['freshness'] ?? [],
            'can_view_financial' => $canFinancial,
            'can_view_coordinates' => $canCoordinate,
            'methodology' => [
                'scope' => 'year_1_baseline_cross_sectional_spatial_decision_support',
                'year' => 1,
                'primary_classification_only' => true,
                'minimum_group_size' => $minimumGroupSize,
                'source_values_preserved' => true,
                'anomalies_excluded' => false,
                'longitudinal_analysis' => false,
                'forecasting' => false,
                'causal_inference' => false,
                'automatic_recommendation' => false,
                'potential_rule' => 'lower_quartile_business_count_and_at_or_above_reference_median',
                'micro_spatial_rule' => 'same_primary_type_haversine_context_only_not_decision_filter',
            ],
        ];

        $payload['decision_insights'] = $this->decisionInsights($payload);

        return $payload;
    }

    private function competitionByDistrict(
        array $filters,
        int $typeId,
        bool $canFinancial,
        int $minimumGroupSize
    ): array {
        $comparisonFilters = array_diff_key($filters, [
            'district_id' => true,
            'village_id' => true,
            'type_id' => true,
        ]);

        $districts = $this->cityDistricts();
        $typeRows = $this->typeRowsAcrossCity(
            $comparisonFilters,
            $typeId,
            $canFinancial
        );
        $overallCounts = $this->overallDistrictCounts($comparisonFilters);
        $qualityIds = $this->qualityAffectedIds(
            $typeRows->pluck('umkm_id')->unique()->all()
        );

        $economicMetric = $canFinancial
            ? $this->chooseEconomicMetric($typeRows->unique('umkm_id')->values(), $minimumGroupSize)
            : null;
        $cityEconomicMedian = $economicMetric
            ? $this->median($this->numericValues($typeRows->unique('umkm_id')->values(), $economicMetric))
            : null;

        $positiveCounts = $districts->map(function (array $district) use ($typeRows): int {
            return $typeRows
                ->where('district_id', $district['id'])
                ->pluck('umkm_id')
                ->unique()
                ->count();
        })->filter(fn (int $count): bool => $count > 0)->values()->all();

        $q1 = $this->quantile($positiveCounts, 0.25);
        $q3 = $this->quantile($positiveCounts, 0.75);

        $rows = $districts->map(function (array $district) use (
            $typeRows,
            $overallCounts,
            $qualityIds,
            $minimumGroupSize,
            $q1,
            $q3,
            $economicMetric,
            $cityEconomicMedian,
            $filters
        ): array {
            $rows = $typeRows
                ->where('district_id', $district['id'])
                ->unique('umkm_id')
                ->values();

            $aggregate = $this->aggregateRows(
                $rows,
                $qualityIds,
                $minimumGroupSize,
                $economicMetric
            );
            $density = $this->densityLabel($aggregate['business_count'], $q1, $q3);
            $economic = $aggregate['economic'];
            $economicStrong = $economicMetric !== null
                && ($economic['visible'] ?? false)
                && $economic['median'] !== null
                && $cityEconomicMedian !== null
                && $economic['median'] >= $cityEconomicMedian;
            $potential = $density === 'Rendah' && $economicStrong;
            $districtTotal = (int) ($overallCounts[$district['id']] ?? 0);

            $drillFilters = array_filter(
                array_merge(
                    array_diff_key($filters, ['district_id' => true, 'village_id' => true]),
                    ['district_id' => $district['id']]
                ),
                static fn ($value): bool => $value !== null && $value !== ''
            );

            return array_merge($district, $aggregate, [
                'district_total_umkm' => $districtTotal,
                'share_percent' => $this->percent(
                    $aggregate['business_count'],
                    $districtTotal
                ),
                'density_level' => $density,
                'potential_relative' => $potential,
                'context_label' => $this->competitionContext(
                    $aggregate['business_count'],
                    $density,
                    $economic,
                    $cityEconomicMedian,
                    $economicMetric,
                    $minimumGroupSize
                ),
                'economic_metric' => $economicMetric,
                'economic_metric_label' => $economicMetric
                    ? self::ECONOMIC_METRICS[$economicMetric]
                    : null,
                'quality_warning' => $aggregate['quality_affected'] > 0,
                'drill_down_url' => route('admin-dinas.umkm.index', $drillFilters),
                'map_url' => route('admin-dinas.analytics.spatial', $drillFilters),
            ]);
        })->values();

        return [
            $rows->all(),
            [
                'business_count_q1' => $q1,
                'business_count_q3' => $q3,
                'economic_metric' => $economicMetric,
                'economic_metric_label' => $economicMetric
                    ? self::ECONOMIC_METRICS[$economicMetric]
                    : null,
                'reference_median' => $cityEconomicMedian,
                'reference_sample_count' => $economicMetric
                    ? count($this->numericValues($typeRows->unique('umkm_id')->values(), $economicMetric))
                    : 0,
                'minimum_group_size' => $minimumGroupSize,
                'potential_count' => $rows->where('potential_relative', true)->count(),
            ],
        ];
    }

    private function opportunityTypes(
        array $filters,
        int $districtId,
        bool $canFinancial,
        int $minimumGroupSize
    ): array {
        $districtFilters = array_merge(
            array_diff_key($filters, ['type_id' => true]),
            ['district_id' => $districtId]
        );

        $rows = $this->districtTypeRows($districtFilters, $canFinancial);
        $overallRows = $this->districtOverallRows($districtFilters, $canFinancial);
        $qualityIds = $this->qualityAffectedIds(
            $rows->pluck('umkm_id')->unique()->all()
        );

        $economicMetric = $canFinancial
            ? $this->chooseEconomicMetric($overallRows, $minimumGroupSize)
            : null;
        $referenceMedian = $economicMetric
            ? $this->median($this->numericValues($overallRows, $economicMetric))
            : null;

        $groups = $rows->groupBy('type_id');
        $positiveCounts = $groups->map(
            fn (Collection $group): int => $group->pluck('umkm_id')->unique()->count()
        )->filter(fn (int $count): bool => $count > 0)->values()->all();
        $q1 = $this->quantile($positiveCounts, 0.25);

        $payload = $groups->map(function (Collection $group) use (
            $qualityIds,
            $minimumGroupSize,
            $economicMetric,
            $referenceMedian,
            $q1,
            $filters,
            $districtId
        ): array {
            $group = $group->unique('umkm_id')->values();
            $first = $group->first();
            $aggregate = $this->aggregateRows(
                $group,
                $qualityIds,
                $minimumGroupSize,
                $economicMetric
            );
            $isLowCount = $q1 !== null
                && $aggregate['business_count'] <= $q1;
            $economicStrong = $economicMetric !== null
                && ($aggregate['economic']['visible'] ?? false)
                && $aggregate['economic']['median'] !== null
                && $referenceMedian !== null
                && $aggregate['economic']['median'] >= $referenceMedian;
            $potential = $isLowCount && $economicStrong;

            $drillFilters = array_filter(
                array_merge(
                    array_diff_key($filters, ['type_id' => true]),
                    [
                        'district_id' => $districtId,
                        'type_id' => (int) $first->type_id,
                    ]
                ),
                static fn ($value): bool => $value !== null && $value !== ''
            );

            return array_merge($aggregate, [
                'type_id' => (int) $first->type_id,
                'type_name' => (string) $first->type_name,
                'low_count_group' => $isLowCount,
                'potential_relative' => $potential,
                'context_label' => $this->opportunityContext(
                    $aggregate['business_count'],
                    $isLowCount,
                    $aggregate['economic'],
                    $referenceMedian,
                    $potential,
                    $economicMetric,
                    $minimumGroupSize
                ),
                'economic_metric' => $economicMetric,
                'economic_metric_label' => $economicMetric
                    ? self::ECONOMIC_METRICS[$economicMetric]
                    : null,
                'quality_warning' => $aggregate['quality_affected'] > 0,
                'drill_down_url' => route('admin-dinas.umkm.index', $drillFilters),
                'map_url' => route('admin-dinas.analytics.spatial', $drillFilters),
            ]);
        })->sort(function (array $left, array $right): int {
            $potentialOrder = ((int) ! $left['potential_relative'])
                <=> ((int) ! $right['potential_relative']);

            if ($potentialOrder !== 0) {
                return $potentialOrder;
            }

            $countOrder = $left['business_count'] <=> $right['business_count'];
            if ($countOrder !== 0) {
                return $countOrder;
            }

            return strcmp($left['type_name'], $right['type_name']);
        })->values();

        return [
            $payload->all(),
            [
                'business_count_q1' => $q1,
                'economic_metric' => $economicMetric,
                'economic_metric_label' => $economicMetric
                    ? self::ECONOMIC_METRICS[$economicMetric]
                    : null,
                'reference_median' => $referenceMedian,
                'reference_sample_count' => $economicMetric
                    ? count($this->numericValues($overallRows, $economicMetric))
                    : 0,
                'minimum_group_size' => $minimumGroupSize,
                'potential_count' => $payload->where('potential_relative', true)->count(),
            ],
        ];
    }

    private function microSpatial(
        array $filters,
        ?int $typeId,
        bool $canCoordinate
    ): array {
        $base = [
            'available' => false,
            'pool_count' => 0,
            'focus_count' => 0,
            'rows' => [],
            'cross_administrative_boundary' => true,
        ];

        if (! $canCoordinate) {
            $base['reason'] = 'coordinate_permission_required';

            return $base;
        }

        if (! $typeId) {
            $base['reason'] = 'type_required';

            return $base;
        }

        if (! $this->coordinateSchemaReady()) {
            $base['reason'] = 'coordinate_schema_unavailable';

            return $base;
        }

        $poolFilters = array_diff_key($filters, [
            'district_id' => true,
            'village_id' => true,
            'type_id' => true,
        ]);

        $pool = $this->coordinatePool($poolFilters, $typeId);
        $base['pool_count'] = $pool->count();

        if ($pool->isEmpty()) {
            $base['available'] = true;
            $base['reason'] = 'no_coordinate_points';

            return $base;
        }

        $focus = $pool;

        if (! empty($filters['district_id'])) {
            $districtId = (int) $filters['district_id'];
            $focus = $focus->where('district_id', $districtId);
        }

        if (! empty($filters['village_id'])) {
            $villageId = (int) $filters['village_id'];
            $focus = $focus->where('village_id', $villageId);
        }

        $focus = $focus->values();
        $base['focus_count'] = $focus->count();
        $qualityIds = $this->qualityAffectedIds(
            $focus->pluck('umkm_id')->unique()->all()
        );

        $rows = $focus->map(function ($point) use (
            $pool,
            $qualityIds
        ): array {
            $distances = $pool
                ->reject(fn ($candidate): bool => (int) $candidate->umkm_id === (int) $point->umkm_id)
                ->map(fn ($candidate): float => $this->haversineMeters(
                    (float) $point->latitude,
                    (float) $point->longitude,
                    (float) $candidate->latitude,
                    (float) $candidate->longitude
                ))
                ->sort()
                ->values();

            return [
                'umkm_id' => (int) $point->umkm_id,
                'umkm_code' => (string) $point->umkm_code,
                'business_name' => (string) $point->business_name,
                'district_id' => (int) $point->district_id,
                'district_name' => (string) $point->district_name,
                'village_id' => $point->village_id !== null ? (int) $point->village_id : null,
                'village_name' => $point->village_name,
                'nearest_same_type_meters' => $distances->isNotEmpty()
                    ? round((float) $distances->first(), 1)
                    : null,
                'neighbors_250m' => $distances->filter(fn (float $distance): bool => $distance <= 250)->count(),
                'neighbors_500m' => $distances->filter(fn (float $distance): bool => $distance <= 500)->count(),
                'neighbors_1000m' => $distances->filter(fn (float $distance): bool => $distance <= 1000)->count(),
                'quality_warning' => $qualityIds->contains((int) $point->umkm_id),
                'detail_url' => route('admin-dinas.umkm.show', (int) $point->umkm_id),
            ];
        })->values();

        $rows = $rows->sort(function (array $left, array $right): int {
            $leftDistance = $left['nearest_same_type_meters'];
            $rightDistance = $right['nearest_same_type_meters'];

            if ($leftDistance === null && $rightDistance === null) {
                return strcmp($left['business_name'], $right['business_name']);
            }

            if ($leftDistance === null) {
                return 1;
            }

            if ($rightDistance === null) {
                return -1;
            }

            $distanceOrder = $leftDistance <=> $rightDistance;

            return $distanceOrder !== 0
                ? $distanceOrder
                : strcmp($left['business_name'], $right['business_name']);
        })->values();

        $base['available'] = true;
        $base['reason'] = null;
        $base['rows'] = $rows->all();
        return $base;
    }

    private function typeRowsAcrossCity(
        array $filters,
        int $typeId,
        bool $canFinancial
    ): Collection {
        if (! $this->analyticsSchemaReady()) {
            return collect();
        }

        $select = [
            'u.id as umkm_id',
            'd.id as district_id',
            'd.name as district_name',
            'b.employee_count',
        ];

        if ($canFinancial) {
            $select[] = 'b.annual_sales_amount';
            $select[] = 'b.baseline_monthly_revenue';
        }

        $query = $this->baseQuery($filters)
            ->join('umkm_business_classifications as c', function ($join) use ($typeId): void {
                $join->on('c.umkm_id', '=', 'u.id')
                    ->where('c.is_primary', 1)
                    ->where('c.business_type_id', $typeId);
            })
            ->join('umkm_locations as l', 'l.umkm_id', '=', 'u.id')
            ->join('regions as d', 'd.id', '=', 'l.district_region_id')
            ->leftJoin('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'u.id')
            ->where('d.level', 'district')
            ->where('d.parent_code', $this->cityCode())
            ->select($select)
            ->distinct();

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query->get();
    }

    private function districtTypeRows(
        array $filters,
        bool $canFinancial
    ): Collection {
        if (! $this->analyticsSchemaReady()) {
            return collect();
        }

        $select = [
            'u.id as umkm_id',
            't.id as type_id',
            't.name as type_name',
            'b.employee_count',
        ];

        if ($canFinancial) {
            $select[] = 'b.annual_sales_amount';
            $select[] = 'b.baseline_monthly_revenue';
        }

        $query = $this->baseQuery($filters)
            ->join('umkm_business_classifications as c', function ($join): void {
                $join->on('c.umkm_id', '=', 'u.id')
                    ->where('c.is_primary', 1);
            })
            ->join('business_type_references as t', 't.id', '=', 'c.business_type_id')
            ->leftJoin('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'u.id')
            ->whereNotNull('c.business_type_id')
            ->when(
                Schema::hasColumn('business_type_references', 'is_active'),
                fn (Builder $query) => $query->where('t.is_active', 1)
            )
            ->select($select)
            ->distinct();

        return $query->get();
    }

    private function districtOverallRows(
        array $filters,
        bool $canFinancial
    ): Collection {
        if (! Schema::hasTable('umkm_baseline_profiles')) {
            return collect();
        }

        $select = [
            'u.id as umkm_id',
            'b.employee_count',
        ];

        if ($canFinancial) {
            $select[] = 'b.annual_sales_amount';
            $select[] = 'b.baseline_monthly_revenue';
        }

        return $this->baseQuery($filters)
            ->leftJoin('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'u.id')
            ->select($select)
            ->distinct()
            ->get()
            ->unique('umkm_id')
            ->values();
    }

    private function coordinatePool(array $filters, int $typeId): Collection
    {
        $query = $this->baseQuery($filters)
            ->join('umkm_business_classifications as c', function ($join) use ($typeId): void {
                $join->on('c.umkm_id', '=', 'u.id')
                    ->where('c.is_primary', 1)
                    ->where('c.business_type_id', $typeId);
            })
            ->join('umkm_locations as l', 'l.umkm_id', '=', 'u.id')
            ->join('regions as d', 'd.id', '=', 'l.district_region_id')
            ->leftJoin('regions as v', 'v.id', '=', 'l.village_region_id')
            ->where('d.level', 'district')
            ->where('d.parent_code', $this->cityCode())
            ->where('l.coordinate_status', 'terpetakan')
            ->whereNotNull('l.latitude')
            ->whereNotNull('l.longitude')
            ->select([
                'u.id as umkm_id',
                'u.umkm_code',
                'u.business_name',
                'd.id as district_id',
                'd.name as district_name',
                'v.id as village_id',
                'v.name as village_name',
                'l.latitude',
                'l.longitude',
            ])
            ->orderBy('u.id')
            ->orderBy('l.id');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query->get()->unique('umkm_id')->values();
    }

    private function overallDistrictCounts(array $filters): Collection
    {
        if (! Schema::hasTable('umkm_locations')) {
            return collect();
        }

        $query = DB::query()
            ->fromSub($this->baseQuery($filters)->select('u.id'), 'base')
            ->join('umkm_locations as l', 'l.umkm_id', '=', 'base.id')
            ->whereNotNull('l.district_region_id');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query
            ->selectRaw('l.district_region_id, COUNT(DISTINCT base.id) AS total_umkm')
            ->groupBy('l.district_region_id')
            ->pluck('total_umkm', 'l.district_region_id')
            ->map(fn ($value): int => (int) $value);
    }

    private function aggregateRows(
        Collection $rows,
        Collection $qualityIds,
        int $minimumGroupSize,
        ?string $economicMetric
    ): array {
        $rows = $rows->unique('umkm_id')->values();
        $businessCount = $rows->count();

        $employees = $this->numericValues($rows, 'employee_count');
        $economicValues = $economicMetric
            ? $this->numericValues($rows, $economicMetric)
            : [];
        $economicVisible = $economicMetric !== null
            && $businessCount >= $minimumGroupSize
            && count($economicValues) >= $minimumGroupSize;

        return [
            'business_count' => $businessCount,
            'employees_total' => (int) round(array_sum($employees)),
            'quality_affected' => $rows->pluck('umkm_id')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $qualityIds->contains($id))
                ->unique()
                ->count(),
            'economic' => [
                'sample_count' => count($economicValues),
                'visible' => $economicVisible,
                'median' => $economicVisible ? $this->median($economicValues) : null,
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

        if (! empty($filters['quality_status'])) {
            $query->where('u.quality_status', (string) $filters['quality_status']);
        }

        $this->applyExistsFilter(
            $query,
            $filters,
            'district_id',
            'umkm_locations',
            'district_region_id',
            'fd'
        );
        $this->applyExistsFilter(
            $query,
            $filters,
            'village_id',
            'umkm_locations',
            'village_region_id',
            'fv'
        );
        $this->applyExistsFilter(
            $query,
            $filters,
            'category_id',
            'umkm_business_classifications',
            'business_category_id',
            'fc'
        );
        $this->applyExistsFilter(
            $query,
            $filters,
            'type_id',
            'umkm_business_classifications',
            'business_type_id',
            'ft'
        );
        $this->applyExistsFilter(
            $query,
            $filters,
            'marketing_method_id',
            'umkm_baseline_profiles',
            'marketing_method_id',
            'fm'
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

    private function contextTypeCount(array $filters): int
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasColumn('umkm_business_classifications', 'business_type_id')
        ) {
            return 0;
        }

        return (int) DB::table('umkm_business_classifications as c')
            ->whereIn('c.umkm_id', $this->baseQuery($filters)->select('u.id'))
            ->where('c.is_primary', 1)
            ->whereNotNull('c.business_type_id')
            ->distinct()
            ->count('c.business_type_id');
    }

    private function contextDistrictCount(array $filters): int
    {
        if (! Schema::hasTable('umkm_locations')) {
            return 0;
        }

        $query = DB::table('umkm_locations as l')
            ->whereIn('l.umkm_id', $this->baseQuery($filters)->select('u.id'))
            ->whereNotNull('l.district_region_id');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return (int) $query->distinct()->count('l.district_region_id');
    }

    private function coordinateMappedCount(array $filters): int
    {
        if (! $this->coordinateSchemaReady()) {
            return 0;
        }

        $query = DB::table('umkm_locations as l')
            ->whereIn('l.umkm_id', $this->baseQuery($filters)->select('u.id'))
            ->where('l.coordinate_status', 'terpetakan')
            ->whereNotNull('l.latitude')
            ->whereNotNull('l.longitude');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return (int) $query->distinct()->count('l.umkm_id');
    }

    private function cityDistricts(): Collection
    {
        if (! Schema::hasTable('regions')) {
            return collect();
        }

        return DB::table('regions')
            ->where('level', 'district')
            ->where('parent_code', $this->cityCode())
            ->when(
                Schema::hasColumn('regions', 'is_active'),
                fn (Builder $query) => $query->where('is_active', 1)
            )
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'code' => (string) $row->code,
                'name' => (string) $row->name,
            ]);
    }

    private function resolveOption(iterable $items, mixed $selectedId): ?array
    {
        if ($selectedId === null || $selectedId === '') {
            return null;
        }

        foreach ($items as $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : ($item->id ?? null);
            $name = is_array($item) ? ($item['name'] ?? null) : ($item->name ?? null);
            $code = is_array($item) ? ($item['code'] ?? null) : ($item->code ?? null);

            if ((string) $id === (string) $selectedId) {
                return [
                    'id' => (int) $id,
                    'name' => (string) $name,
                    'code' => $code !== null ? (string) $code : null,
                ];
            }
        }

        return null;
    }

    private function chooseEconomicMetric(
        Collection $rows,
        int $minimumGroupSize
    ): ?string {
        foreach (array_keys(self::ECONOMIC_METRICS) as $column) {
            if (count($this->numericValues($rows, $column)) >= $minimumGroupSize) {
                return $column;
            }
        }

        return null;
    }

    private function numericValues(Collection $rows, string $column): array
    {
        return $rows
            ->map(fn ($row) => $row->{$column} ?? null)
            ->filter(fn ($value): bool => $value !== null && $value !== '' && is_numeric($value))
            ->map(fn ($value): float => (float) $value)
            ->values()
            ->all();
    }

    private function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }

    private function quantile(array $values, float $probability): ?float
    {
        $values = array_values(array_filter(
            $values,
            fn ($value): bool => is_numeric($value)
        ));

        if ($values === []) {
            return null;
        }

        sort($values, SORT_NUMERIC);

        if (count($values) === 1) {
            return (float) $values[0];
        }

        $position = (count($values) - 1) * $probability;
        $lower = (int) floor($position);
        $upper = (int) ceil($position);

        if ($lower === $upper) {
            return (float) $values[$lower];
        }

        $weight = $position - $lower;

        return ((float) $values[$lower] * (1 - $weight))
            + ((float) $values[$upper] * $weight);
    }

    private function densityLabel(int $count, ?float $q1, ?float $q3): string
    {
        if ($count === 0) {
            return 'Belum ada';
        }

        if ($q1 === null || $q3 === null || abs($q3 - $q1) < 0.000001) {
            return 'Sedang';
        }

        if ($count <= $q1) {
            return 'Rendah';
        }

        if ($count >= $q3) {
            return 'Tinggi';
        }

        return 'Sedang';
    }

    private function competitionContext(
        int $businessCount,
        string $density,
        array $economic,
        ?float $referenceMedian,
        ?string $economicMetric,
        int $minimumGroupSize
    ): string {
        if ($businessCount === 0) {
            return 'Belum ada usaha sejenis tercatat';
        }

        if ($businessCount < $minimumGroupSize) {
            return 'Konsentrasi teridentifikasi; agregat ekonomi dibatasi';
        }

        if (
            $economicMetric === null
            || ! ($economic['visible'] ?? false)
            || $economic['median'] === null
            || $referenceMedian === null
        ) {
            return 'Konsentrasi ' . mb_strtolower($density)
                . '; sinyal ekonomi belum cukup atau tidak tersedia pada kewenangan ini';
        }

        $economicHigh = $economic['median'] >= $referenceMedian;

        return match (true) {
            $density === 'Rendah' && $economicHigh => 'Indikasi potensi wilayah relatif',
            $density === 'Tinggi' && $economicHigh => 'Pasar aktif-kompetitif',
            $density === 'Tinggi' && ! $economicHigh => 'Indikasi tekanan persaingan relatif',
            $density === 'Rendah' && ! $economicHigh => 'Aktivitas relatif rendah',
            default => 'Kondisi menengah',
        };
    }

    private function opportunityContext(
        int $businessCount,
        bool $isLowCount,
        array $economic,
        ?float $referenceMedian,
        bool $potential,
        ?string $economicMetric,
        int $minimumGroupSize
    ): string {
        if ($businessCount < $minimumGroupSize) {
            return 'Data agregat belum cukup untuk indikasi potensi';
        }

        if (
            $economicMetric === null
            || ! ($economic['visible'] ?? false)
            || $economic['median'] === null
            || $referenceMedian === null
        ) {
            return $isLowCount
                ? 'Jumlah usaha relatif sedikit; sinyal ekonomi belum cukup'
                : 'Sinyal ekonomi belum cukup untuk interpretasi';
        }

        if ($potential) {
            return 'Indikasi potensi relatif';
        }

        if ($isLowCount && $economic['median'] < $referenceMedian) {
            return 'Aktivitas relatif rendah';
        }

        return 'Kondisi pembanding';
    }

    private function qualityAffectedIds(array $umkmIds): Collection
    {
        $ids = collect($umkmIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty() || ! Schema::hasTable('umkm_data_quality_flags')) {
            return collect();
        }

        $query = DB::table('umkm_data_quality_flags')
            ->whereIn('umkm_id', $ids->all());

        if (Schema::hasColumn('umkm_data_quality_flags', 'status')) {
            $query->where('status', 'open');
        }

        return $query->distinct()->pluck('umkm_id')->map(fn ($id): int => (int) $id);
    }

    private function decisionInsights(array $payload): array
    {
        $insights = [];
        $selectedType = $payload['selected_type'] ?? null;
        $selectedDistrict = $payload['selected_district'] ?? null;
        $competition = collect($payload['competition_by_district'] ?? []);
        $opportunities = collect($payload['opportunity_types'] ?? []);

        if (! $selectedType) {
            $insights[] = [
                'level' => 'info',
                'title' => 'Pilih jenis usaha',
                'finding' => 'Jenis usaha belum dipilih.',
                'consideration' => 'Pilih jenis usaha untuk membandingkan konsentrasi dan konteks usaha sejenis antarwilayah.',
            ];
        } elseif ($competition->isNotEmpty()) {
            $highest = $competition->sortByDesc('business_count')->first();
            $insights[] = [
                'level' => 'attention',
                'title' => 'Konsentrasi usaha sejenis',
                'finding' => sprintf(
                    '%s memiliki jumlah %s tertinggi dalam pembanding: %d usaha.',
                    $highest['name'],
                    $selectedType['name'],
                    (int) $highest['business_count']
                ),
                'consideration' => 'Gunakan hasil ini sebagai indikator konsentrasi, bukan bukti tunggal tingkat persaingan.',
            ];

            $potentialCount = $competition->where('potential_relative', true)->count();
            if ($potentialCount > 0) {
                $insights[] = [
                    'level' => 'opportunity',
                    'title' => 'Indikasi potensi wilayah relatif',
                    'finding' => $potentialCount . ' wilayah memenuhi rule transparan potensi relatif.',
                    'consideration' => 'Tinjau jumlah usaha, median sinyal ekonomi, mutu data, dan konteks lapangan sebelum tindak lanjut.',
                ];
            }
        }

        if ($selectedDistrict && $opportunities->isNotEmpty()) {
            $potentialTypeCount = $opportunities->where('potential_relative', true)->count();
            $insights[] = [
                'level' => $potentialTypeCount > 0 ? 'opportunity' : 'info',
                'title' => 'Komposisi jenis usaha wilayah',
                'finding' => $potentialTypeCount > 0
                    ? $potentialTypeCount . ' jenis usaha menunjukkan indikasi potensi relatif pada ' . $selectedDistrict['name'] . '.'
                    : 'Belum ada jenis usaha yang memenuhi rule indikasi potensi relatif pada ' . $selectedDistrict['name'] . '.',
                'consideration' => 'Label didasarkan pada jumlah usaha dan sinyal ekonomi baseline, bukan prediksi keberhasilan.',
            ];
        }

        if (($payload['quality_warning'] ?? false) === true) {
            $insights[] = [
                'level' => 'warning',
                'title' => 'Mutu data perlu diperhatikan',
                'finding' => 'Sebagian record dalam analisis memiliki flag mutu terbuka.',
                'consideration' => 'Nilai sumber tetap dipertahankan apa adanya; interpretasikan hasil agregat dengan hati-hati.',
            ];
        }

        return $insights;
    }

    private function haversineMeters(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB
    ): float {
        $earthRadius = 6371008.8;
        $lat1 = deg2rad($latitudeA);
        $lat2 = deg2rad($latitudeB);
        $deltaLat = deg2rad($latitudeB - $latitudeA);
        $deltaLon = deg2rad($longitudeB - $longitudeA);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));

        return $earthRadius * $c;
    }

    private function percent(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 2);
    }

    private function cityCode(): string
    {
        return (string) config('umkm.landing_region.city_code', '16.73');
    }

    private function analyticsSchemaReady(): bool
    {
        return Schema::hasTable('umkms')
            && Schema::hasTable('umkm_business_classifications')
            && Schema::hasTable('business_type_references')
            && Schema::hasTable('umkm_locations')
            && Schema::hasTable('regions')
            && Schema::hasTable('umkm_baseline_profiles');
    }

    private function coordinateSchemaReady(): bool
    {
        return Schema::hasTable('umkm_locations')
            && Schema::hasColumn('umkm_locations', 'coordinate_status')
            && Schema::hasColumn('umkm_locations', 'latitude')
            && Schema::hasColumn('umkm_locations', 'longitude');
    }

    private function operationalStatuses(): array
    {
        $statuses = array_values(array_filter(array_map(
            fn ($status): string => trim((string) $status),
            (array) config('umkm.data.operational_statuses', ['resmi', 'terbatas'])
        )));

        return $statuses === [] ? ['resmi', 'terbatas'] : $statuses;
    }
}