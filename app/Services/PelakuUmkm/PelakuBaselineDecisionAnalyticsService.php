<?php

namespace App\Services\PelakuUmkm;

use App\Models\User;
use App\Support\PublicLanding\PublicLandingDataFreshness;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PelakuBaselineDecisionAnalyticsService
{
    private const DEFAULT_MIN_GROUP_SIZE = 3;

    private const ECONOMIC_METRICS = [
        'baseline_monthly_revenue' => 'Omzet bulanan',
        'annual_sales_amount' => 'Penjualan tahunan',
    ];

    public function __construct(private PelakuWorkspaceAccessService $accessService)
    {
    }

    public function build(User $user, array $filters): array
    {
        $minimumGroupSize = max(
            3,
            (int) config(
                'umkm.pelaku_decision_analytics.minimum_group_size',
                self::DEFAULT_MIN_GROUP_SIZE
            )
        );

        $owned = $this->accessService->ownedUmkmQuery($user)
            ->orderBy('business_name')
            ->get(['id', 'umkm_code', 'business_name', 'quality_status']);

        $selectedUmkm = $this->resolveOwnedUmkm($owned, $filters['umkm_id'] ?? null);
        $cityCode = (string) config('umkm.landing_region.city_code', '16.73');
        $districts = $this->cityDistricts($cityCode);

        $base = [
            'filters' => $filters,
            'owned_umkms' => $owned->map(fn ($row) => [
                'id' => (int) $row->id,
                'umkm_code' => (string) $row->umkm_code,
                'business_name' => (string) $row->business_name,
                'quality_status' => $row->quality_status,
            ])->values()->all(),
            'selected_umkm' => null,
            'available_types' => [],
            'selected_type' => null,
            'districts' => $districts->values()->all(),
            'selected_district' => null,
            'own_district_ids' => [],
            'position' => null,
            'competition_by_district' => [],
            'competition_summary' => null,
            'opportunity_types' => [],
            'opportunity_summary' => null,
            'quality_warning' => false,
            'freshness' => PublicLandingDataFreshness::latest(),
            'methodology' => [
                'scope' => 'baseline_cross_sectional_spatial',
                'year' => 1,
                'minimum_group_size' => $minimumGroupSize,
                'primary_classification_only' => true,
                'source_values_preserved' => true,
                'anomalies_excluded' => false,
                'longitudinal_analysis' => false,
                'forecasting' => false,
                'automatic_recommendation' => false,
                'potential_rule' => 'lower_quartile_business_count_and_at_or_above_reference_median',
            ],
        ];

        if (! $selectedUmkm) {
            return $base;
        }

        $availableTypes = $this->primaryTypesForUmkm((int) $selectedUmkm->id);
        $selectedType = $this->resolveSelectedType($availableTypes, $filters['type_id'] ?? null);
        $ownDistricts = $this->districtsForUmkm((int) $selectedUmkm->id, $cityCode);
        $selectedDistrict = $this->resolveSelectedDistrict(
            $districts,
            $ownDistricts,
            $filters['district_id'] ?? null
        );

        $base['selected_umkm'] = [
            'id' => (int) $selectedUmkm->id,
            'umkm_code' => (string) $selectedUmkm->umkm_code,
            'business_name' => (string) $selectedUmkm->business_name,
            'quality_status' => $selectedUmkm->quality_status,
        ];
        $base['available_types'] = $availableTypes->values()->all();
        $base['selected_type'] = $selectedType;
        $base['own_district_ids'] = $ownDistricts->pluck('id')->map(fn ($id) => (int) $id)->all();
        $base['selected_district'] = $selectedDistrict;

        if ($selectedType) {
            $typeRows = $this->rowsForBusinessType(
                (int) $selectedType['id'],
                $cityCode
            );

            $ownedIds = $owned->pluck('id')->map(fn ($id) => (int) $id)->all();
            $base['position'] = $this->positionPayload(
                (int) $selectedUmkm->id,
                $typeRows,
                $ownedIds,
                $minimumGroupSize
            );

            [$competitionRows, $competitionSummary] = $this->competitionPayload(
                $districts,
                $typeRows,
                $base['own_district_ids'],
                $minimumGroupSize
            );

            $base['competition_by_district'] = $competitionRows;
            $base['competition_summary'] = $competitionSummary;
        }

        if ($selectedDistrict) {
            [$opportunityRows, $opportunitySummary] = $this->opportunityPayload(
                (int) $selectedDistrict['id'],
                $selectedDistrict,
                $minimumGroupSize
            );
            $base['opportunity_types'] = $opportunityRows;
            $base['opportunity_summary'] = $opportunitySummary;
        }

        $base['quality_warning'] = $this->hasQualityWarning($base);

        return $base;
    }

    private function resolveOwnedUmkm(Collection $owned, mixed $requestedId): ?object
    {
        if ($requestedId !== null && $requestedId !== '') {
            $id = (int) $requestedId;
            $selected = $owned->firstWhere('id', $id);

            if (! $selected) {
                abort(404);
            }

            return $selected;
        }

        return $owned->count() === 1 ? $owned->first() : null;
    }

    private function primaryTypesForUmkm(int $umkmId): Collection
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_type_references')
        ) {
            return collect();
        }

        return DB::table('umkm_business_classifications as c')
            ->join('business_type_references as t', 't.id', '=', 'c.business_type_id')
            ->where('c.umkm_id', $umkmId)
            ->where('c.is_primary', 1)
            ->when(
                Schema::hasColumn('business_type_references', 'is_active'),
                fn (Builder $query) => $query->where('t.is_active', 1)
            )
            ->whereNotNull('c.business_type_id')
            ->select('t.id', 't.name')
            ->distinct()
            ->orderBy('t.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
            ]);
    }

    private function resolveSelectedType(Collection $types, mixed $requestedId): ?array
    {
        if ($requestedId !== null && $requestedId !== '') {
            $id = (int) $requestedId;
            $selected = $types->first(fn (array $row) => $row['id'] === $id);

            if (! $selected) {
                abort(404);
            }

            return $selected;
        }

        return $types->count() === 1 ? $types->first() : null;
    }

    private function cityDistricts(string $cityCode): Collection
    {
        if (! Schema::hasTable('regions')) {
            return collect();
        }

        return DB::table('regions')
            ->where('level', 'district')
            ->where('parent_code', $cityCode)
            ->when(
                Schema::hasColumn('regions', 'is_active'),
                fn (Builder $query) => $query->where('is_active', 1)
            )
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => (string) $row->code,
                'name' => (string) $row->name,
            ]);
    }

    private function districtsForUmkm(int $umkmId, string $cityCode): Collection
    {
        if (! Schema::hasTable('umkm_locations') || ! Schema::hasTable('regions')) {
            return collect();
        }

        $query = DB::table('umkm_locations as l')
            ->join('regions as d', 'd.id', '=', 'l.district_region_id')
            ->where('l.umkm_id', $umkmId)
            ->where('d.level', 'district')
            ->where('d.parent_code', $cityCode)
            ->select('d.id', 'd.code', 'd.name')
            ->distinct()
            ->orderBy('d.name');

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'code' => (string) $row->code,
            'name' => (string) $row->name,
        ]);
    }

    private function resolveSelectedDistrict(
        Collection $districts,
        Collection $ownDistricts,
        mixed $requestedId
    ): ?array {
        if ($requestedId !== null && $requestedId !== '') {
            $id = (int) $requestedId;
            $selected = $districts->first(fn (array $row) => $row['id'] === $id);

            if (! $selected) {
                abort(404);
            }

            return $selected;
        }

        if ($ownDistricts->count() === 1) {
            $own = $ownDistricts->first();

            return $districts->first(fn (array $row) => $row['id'] === $own['id']);
        }

        return null;
    }

    private function rowsForBusinessType(int $typeId, string $cityCode): Collection
    {
        if (! $this->analyticsSchemaReady()) {
            return collect();
        }

        $query = $this->operationalQuery()
            ->join('umkm_business_classifications as c', function ($join) use ($typeId): void {
                $join->on('c.umkm_id', '=', 'u.id')
                    ->where('c.is_primary', 1)
                    ->where('c.business_type_id', $typeId);
            })
            ->join('umkm_locations as l', 'l.umkm_id', '=', 'u.id')
            ->join('regions as d', 'd.id', '=', 'l.district_region_id')
            ->leftJoin('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'u.id')
            ->where('d.level', 'district')
            ->where('d.parent_code', $cityCode)
            ->select([
                'u.id as umkm_id',
                'd.id as district_id',
                'd.name as district_name',
                'b.capital_amount',
                'b.annual_sales_amount',
                'b.baseline_monthly_revenue',
                'b.employee_count',
            ])
            ->distinct();

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query->get();
    }

    private function rowsForDistrictTypes(int $districtId): Collection
    {
        if (! $this->analyticsSchemaReady()) {
            return collect();
        }

        $query = $this->operationalQuery()
            ->join('umkm_business_classifications as c', function ($join): void {
                $join->on('c.umkm_id', '=', 'u.id')
                    ->where('c.is_primary', 1);
            })
            ->join('business_type_references as t', 't.id', '=', 'c.business_type_id')
            ->join('umkm_locations as l', 'l.umkm_id', '=', 'u.id')
            ->leftJoin('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'u.id')
            ->where('l.district_region_id', $districtId)
            ->whereNotNull('c.business_type_id')
            ->when(
                Schema::hasColumn('business_type_references', 'is_active'),
                fn (Builder $sub) => $sub->where('t.is_active', 1)
            )
            ->select([
                'u.id as umkm_id',
                't.id as type_id',
                't.name as type_name',
                'b.capital_amount',
                'b.annual_sales_amount',
                'b.baseline_monthly_revenue',
                'b.employee_count',
            ])
            ->distinct();

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query->get();
    }

    private function rowsForDistrictOverall(int $districtId): Collection
    {
        if (! Schema::hasTable('umkm_locations') || ! Schema::hasTable('umkm_baseline_profiles')) {
            return collect();
        }

        $query = $this->operationalQuery()
            ->join('umkm_locations as l', 'l.umkm_id', '=', 'u.id')
            ->leftJoin('umkm_baseline_profiles as b', 'b.umkm_id', '=', 'u.id')
            ->where('l.district_region_id', $districtId)
            ->select([
                'u.id as umkm_id',
                'b.capital_amount',
                'b.annual_sales_amount',
                'b.baseline_monthly_revenue',
                'b.employee_count',
            ])
            ->distinct();

        if (Schema::hasColumn('umkm_locations', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query->get()->unique('umkm_id')->values();
    }

    private function operationalQuery(): Builder
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

        return $query;
    }

    private function positionPayload(
        int $selectedUmkmId,
        Collection $typeRows,
        array $ownedIds,
        int $minimumGroupSize
    ): array {
        $ownRow = $typeRows->firstWhere('umkm_id', $selectedUmkmId);

        if (! $ownRow && Schema::hasTable('umkm_baseline_profiles')) {
            $ownRow = DB::table('umkm_baseline_profiles as b')
                ->where('b.umkm_id', $selectedUmkmId)
                ->select([
                    'b.umkm_id',
                    'b.capital_amount',
                    'b.annual_sales_amount',
                    'b.baseline_monthly_revenue',
                    'b.employee_count',
                ])
                ->first();
        }

        $peerRows = $typeRows
            ->reject(fn ($row) => in_array((int) $row->umkm_id, $ownedIds, true))
            ->unique('umkm_id')
            ->values();
        $qualityIds = $this->qualityAffectedIds($peerRows->pluck('umkm_id')->all());
        $peer = $this->aggregateRows($peerRows, $qualityIds, $minimumGroupSize);
        $ownQuality = $this->qualityAffectedIds([$selectedUmkmId])->contains($selectedUmkmId);

        $metrics = [];
        foreach ($this->metricDefinitions() as $key => $definition) {
            $ownValue = $ownRow?->{$definition['column']} ?? null;
            $peerMetric = $peer[$key];
            $metrics[$key] = [
                'label' => $definition['label'],
                'own_value' => $this->numericOrNull($ownValue),
                'peer_median' => $peerMetric['median'],
                'peer_sample_count' => $peerMetric['sample_count'],
                'peer_visible' => $peerMetric['visible'],
                'position' => $peerMetric['visible']
                    ? $this->relativePosition($ownValue, $peerMetric['median'])
                    : null,
            ];
        }

        return [
            'peer_count' => $peer['business_count'],
            'minimum_group_size' => $minimumGroupSize,
            'metrics' => $metrics,
            'own_quality_warning' => $ownQuality,
            'peer_quality_affected' => $peer['quality_affected'],
            'privacy_suppressed' => $peer['business_count'] < $minimumGroupSize,
        ];
    }

    private function competitionPayload(
        Collection $districts,
        Collection $typeRows,
        array $ownDistrictIds,
        int $minimumGroupSize
    ): array {
        $qualityIds = $this->qualityAffectedIds($typeRows->pluck('umkm_id')->unique()->all());
        $cityUnique = $typeRows->unique('umkm_id')->values();
        $economicMetric = $this->chooseEconomicMetric($cityUnique, $minimumGroupSize);
        $cityEconomicMedian = $economicMetric
            ? $this->median($this->numericValues($cityUnique, $economicMetric))
            : null;

        $countValues = $districts->map(function (array $district) use ($typeRows): int {
            return $typeRows
                ->where('district_id', $district['id'])
                ->pluck('umkm_id')
                ->unique()
                ->count();
        })->filter(fn (int $count) => $count > 0)->values()->all();

        $q1 = $this->quantile($countValues, 0.25);
        $q3 = $this->quantile($countValues, 0.75);

        $rows = $districts->map(function (array $district) use (
            $typeRows,
            $qualityIds,
            $minimumGroupSize,
            $q1,
            $q3,
            $economicMetric,
            $cityEconomicMedian,
            $ownDistrictIds
        ): array {
            $rows = $typeRows->where('district_id', $district['id'])->unique('umkm_id')->values();
            $aggregate = $this->aggregateRows($rows, $qualityIds, $minimumGroupSize);
            $density = $this->densityLabel($aggregate['business_count'], $q1, $q3);
            $economicPayload = $economicMetric
                ? $aggregate[$this->metricKeyForColumn($economicMetric)]
                : null;

            return array_merge($district, $aggregate, [
                'is_own_district' => in_array($district['id'], $ownDistrictIds, true),
                'density_level' => $density,
                'context_label' => $this->competitionContext(
                    $aggregate['business_count'],
                    $density,
                    $economicPayload,
                    $cityEconomicMedian,
                    $minimumGroupSize
                ),
                'economic_metric' => $economicMetric,
                'economic_metric_label' => $economicMetric
                    ? self::ECONOMIC_METRICS[$economicMetric]
                    : null,
                'quality_warning' => $aggregate['quality_affected'] > 0,
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
                    ? count($this->numericValues($cityUnique, $economicMetric))
                    : 0,
                'minimum_group_size' => $minimumGroupSize,
            ],
        ];
    }

    private function opportunityPayload(
        int $districtId,
        array $district,
        int $minimumGroupSize
    ): array {
        $rows = $this->rowsForDistrictTypes($districtId);
        $overallRows = $this->rowsForDistrictOverall($districtId);
        $qualityIds = $this->qualityAffectedIds($rows->pluck('umkm_id')->unique()->all());
        $economicMetric = $this->chooseEconomicMetric($overallRows, $minimumGroupSize);
        $referenceMedian = $economicMetric
            ? $this->median($this->numericValues($overallRows, $economicMetric))
            : null;

        $groups = $rows->groupBy('type_id');
        $positiveCounts = $groups->map(
            fn (Collection $group) => $group->pluck('umkm_id')->unique()->count()
        )->filter(fn (int $count) => $count > 0)->values()->all();
        $q1 = $this->quantile($positiveCounts, 0.25);

        $payload = $groups->map(function (Collection $group) use (
            $qualityIds,
            $minimumGroupSize,
            $economicMetric,
            $referenceMedian,
            $q1
        ): array {
            $group = $group->unique('umkm_id')->values();
            $first = $group->first();
            $aggregate = $this->aggregateRows($group, $qualityIds, $minimumGroupSize);
            $economicPayload = $economicMetric
                ? $aggregate[$this->metricKeyForColumn($economicMetric)]
                : null;
            $isLowCount = $q1 !== null && $aggregate['business_count'] <= $q1;
            $economicStrong = $economicPayload
                && $economicPayload['visible']
                && $referenceMedian !== null
                && $economicPayload['median'] !== null
                && $economicPayload['median'] >= $referenceMedian;
            $potential = $isLowCount && $economicStrong;

            return array_merge($aggregate, [
                'type_id' => (int) $first->type_id,
                'type_name' => (string) $first->type_name,
                'low_count_group' => $isLowCount,
                'potential_relative' => $potential,
                'context_label' => $this->opportunityContext(
                    $aggregate['business_count'],
                    $isLowCount,
                    $economicPayload,
                    $referenceMedian,
                    $potential,
                    $minimumGroupSize
                ),
                'economic_metric' => $economicMetric,
                'economic_metric_label' => $economicMetric
                    ? self::ECONOMIC_METRICS[$economicMetric]
                    : null,
                'quality_warning' => $aggregate['quality_affected'] > 0,
            ]);
        })->sort(function (array $left, array $right): int {
            $potentialOrder = ((int) ! $left['potential_relative']) <=> ((int) ! $right['potential_relative']);
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
                'district' => $district,
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

    private function aggregateRows(
        Collection $rows,
        Collection $qualityIds,
        int $minimumGroupSize
    ): array {
        $rows = $rows->unique('umkm_id')->values();
        $businessCount = $rows->count();
        $result = [
            'business_count' => $businessCount,
            'quality_affected' => $rows->pluck('umkm_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $qualityIds->contains($id))
                ->unique()
                ->count(),
            'privacy_suppressed' => $businessCount > 0 && $businessCount < $minimumGroupSize,
        ];

        foreach ($this->metricDefinitions() as $key => $definition) {
            $values = $this->numericValues($rows, $definition['column']);
            $visible = $businessCount >= $minimumGroupSize
                && count($values) >= $minimumGroupSize;

            $result[$key] = [
                'label' => $definition['label'],
                'sample_count' => count($values),
                'visible' => $visible,
                'total' => $visible ? array_sum($values) : null,
                'median' => $visible ? $this->median($values) : null,
            ];
        }

        return $result;
    }

    private function metricDefinitions(): array
    {
        return [
            'capital' => ['column' => 'capital_amount', 'label' => 'Modal'],
            'annual_sales' => ['column' => 'annual_sales_amount', 'label' => 'Penjualan tahunan'],
            'monthly_revenue' => ['column' => 'baseline_monthly_revenue', 'label' => 'Omzet bulanan'],
            'employees' => ['column' => 'employee_count', 'label' => 'Tenaga kerja'],
        ];
    }

    private function metricKeyForColumn(string $column): string
    {
        foreach ($this->metricDefinitions() as $key => $definition) {
            if ($definition['column'] === $column) {
                return $key;
            }
        }

        throw new \InvalidArgumentException('Unknown metric column: ' . $column);
    }

    private function chooseEconomicMetric(Collection $rows, int $minimumGroupSize): ?string
    {
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
            ->filter(fn ($value) => $value !== null && $value !== '' && is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->values()
            ->all();
    }

    private function numericOrNull(mixed $value): ?float
    {
        return $value !== null && $value !== '' && is_numeric($value)
            ? (float) $value
            : null;
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
        $values = array_values(array_filter($values, fn ($value) => is_numeric($value)));

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
        ?array $economicPayload,
        ?float $referenceMedian,
        int $minimumGroupSize
    ): string {
        if ($businessCount === 0) {
            return 'Belum ada usaha sejenis tercatat';
        }

        if ($businessCount < $minimumGroupSize) {
            return 'Jumlah usaha tersedia; data ekonomi kelompok dibatasi';
        }

        if (
            ! $economicPayload
            || ! $economicPayload['visible']
            || $economicPayload['median'] === null
            || $referenceMedian === null
        ) {
            return 'Jumlah usaha ' . mb_strtolower($density) . '; data ekonomi belum cukup';
        }

        $economicHigh = $economicPayload['median'] >= $referenceMedian;

        return match (true) {
            $density === 'Rendah' && $economicHigh => 'Kondisi wilayah perlu ditinjau',
            $density === 'Tinggi' && $economicHigh => 'Jumlah usaha tinggi dan data ekonomi tersedia',
            $density === 'Tinggi' && ! $economicHigh => 'Jumlah usaha tinggi; data ekonomi lebih rendah dari pembanding',
            $density === 'Rendah' && ! $economicHigh => 'Aktivitas relatif rendah',
            default => 'Kondisi menengah',
        };
    }

    private function opportunityContext(
        int $businessCount,
        bool $isLowCount,
        ?array $economicPayload,
        ?float $referenceMedian,
        bool $potential,
        int $minimumGroupSize
    ): string {
        if ($businessCount < $minimumGroupSize) {
            return 'Data belum cukup untuk perbandingan';
        }

        if (
            ! $economicPayload
            || ! $economicPayload['visible']
            || $economicPayload['median'] === null
            || $referenceMedian === null
        ) {
            return $isLowCount
                ? 'Jumlah usaha relatif sedikit; data ekonomi belum cukup'
                : 'Data ekonomi belum cukup untuk dibandingkan';
        }

        if ($potential) {
            return 'Kondisi yang perlu ditinjau';
        }

        if ($isLowCount && $economicPayload['median'] < $referenceMedian) {
            return 'Aktivitas relatif rendah';
        }

        return 'Kondisi pembanding';
    }

    private function relativePosition(mixed $ownValue, ?float $peerMedian): ?string
    {
        $own = $this->numericOrNull($ownValue);

        if ($own === null || $peerMedian === null) {
            return null;
        }

        if (abs($own - $peerMedian) < 0.000001) {
            return 'Sama dengan nilai tengah usaha sejenis';
        }

        return $own > $peerMedian
            ? 'Di atas nilai tengah usaha sejenis'
            : 'Di bawah nilai tengah usaha sejenis';
    }

    private function qualityAffectedIds(array $umkmIds): Collection
    {
        $ids = collect($umkmIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
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

        return $query->distinct()->pluck('umkm_id')->map(fn ($id) => (int) $id);
    }

    private function hasQualityWarning(array $payload): bool
    {
        if (($payload['position']['own_quality_warning'] ?? false) === true) {
            return true;
        }

        if ((int) ($payload['position']['peer_quality_affected'] ?? 0) > 0) {
            return true;
        }

        foreach ($payload['competition_by_district'] ?? [] as $row) {
            if (($row['quality_warning'] ?? false) === true) {
                return true;
            }
        }

        foreach ($payload['opportunity_types'] ?? [] as $row) {
            if (($row['quality_warning'] ?? false) === true) {
                return true;
            }
        }

        return false;
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

    private function operationalStatuses(): array
    {
        $statuses = array_values(array_filter(array_map(
            fn ($status) => trim((string) $status),
            (array) config('umkm.data.operational_statuses', ['resmi', 'terbatas'])
        )));

        return $statuses === [] ? ['resmi', 'terbatas'] : $statuses;
    }
}