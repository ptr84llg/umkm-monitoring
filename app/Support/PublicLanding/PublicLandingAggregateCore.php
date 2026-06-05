<?php

namespace App\Support\PublicLanding;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicLandingAggregateCore
{
    public static function payload(array $input): array
    {
        $region = PublicLandingRegionResolver::resolve($input);

        $base = self::baseQuery();
        self::applyRegionFilter($base, $region);

        $total = self::countDistinct(clone $base);
        $mapped = self::countMapped(clone $base);
        $mappedPercent = self::percent($mapped, $total);
        $dominant = self::dominantCategory(clone $base, $total);
        $activeRegions = self::activeRegionCount(clone $base, $region);
        $coverage = self::coveragePercent($activeRegions, $region);

        $cards = self::aggregateCards($total, $mapped, $mappedPercent, $dominant, $activeRegions, $coverage);

        return [
            'scope' => $region['scope'],
            'region' => $region,
            'selection' => [
                'scope' => $region['scope'],
                'label' => $region['context_label'],
                'context_label' => $region['context_label'],
                'region_code' => $region['region_code'],
                'city_code' => $region['city_code'],
                'district_code' => $region['district_code'],
                'village_code' => $region['village_code'],
            ],
            'context_label' => $region['context_label'],
            'aggregate_cards' => array_values($cards),
            'aggregate_card_map' => $cards,
            'preview' => [
                'scope' => $region['scope'],
                'label' => $region['context_label'],
                'context_label' => $region['context_label'],
                'total' => $total,
                'mapped' => $mapped,
                'mapped_percent' => $mappedPercent,
                'mapped_percent_text' => self::formatPercent($mappedPercent),
                'coverage_percent' => self::formatPercent($coverage),
                'coverage_percent_value' => $coverage,
                'dominant' => $dominant['name'],
                'dominant_count' => $dominant['count'],
                'dominant_percent' => self::formatPercent($dominant['percent']),
                'dominant_percent_value' => $dominant['percent'],
                'active_regions' => $activeRegions,
                'aggregate_cards' => array_values($cards),
                'areas' => self::areaDistribution($region),
            ],
            'updated_at' => now()->format('d/m/Y'),
        ];
    }

    private static function baseQuery(): Builder
    {
        $query = DB::table('umkms');

        if (Schema::hasTable('umkm_locations') && Schema::hasColumn('umkm_locations', 'umkm_id')) {
            $query->leftJoin('umkm_locations', 'umkm_locations.umkm_id', '=', 'umkms.id');
        }

        return $query;
    }

    private static function applyRegionFilter(Builder $query, array $region): Builder
    {
        if (! Schema::hasTable('umkm_locations')) {
            return $query;
        }

        if ($region['scope'] === 'city') {
            return self::applyLocationCodeOrId($query, 'city', $region['city_code']);
        }

        if ($region['scope'] === 'district') {
            return self::applyLocationCodeOrId($query, 'district', $region['district_code']);
        }

        if ($region['scope'] === 'village') {
            return self::applyLocationCodeOrId($query, 'village', $region['village_code']);
        }

        return $query;
    }

    private static function applyLocationCodeOrId(Builder $query, string $level, ?string $code): Builder
    {
        if ($code === null) {
            return $query;
        }

        $codeColumns = match ($level) {
            'city' => ['city_code'],
            'district' => ['district_code'],
            'village' => ['village_code'],
            default => [],
        };

        foreach ($codeColumns as $column) {
            if (Schema::hasColumn('umkm_locations', $column)) {
                return $query->where('umkm_locations.' . $column, $code);
            }
        }

        $idColumns = match ($level) {
            'city' => ['city_region_id', 'city_id', 'city_reference_id'],
            'district' => ['district_region_id', 'district_id', 'district_reference_id'],
            'village' => ['village_region_id', 'village_id', 'village_reference_id'],
            default => [],
        };

        $regionId = PublicLandingRegionResolver::regionIdByCode($code);

        if ($regionId !== null) {
            foreach ($idColumns as $column) {
                if (Schema::hasColumn('umkm_locations', $column)) {
                    return $query->where('umkm_locations.' . $column, $regionId);
                }
            }
        }

        if ($level === 'district') {
            return self::applyDistrictViaVillage($query, $code);
        }

        return $query;
    }

    private static function applyDistrictViaVillage(Builder $query, string $districtCode): Builder
    {
        if (Schema::hasColumn('umkm_locations', 'village_code')) {
            return $query->where('umkm_locations.village_code', 'like', $districtCode . '.%');
        }

        if (Schema::hasColumn('umkm_locations', 'village_region_id')) {
            $ids = PublicLandingRegionResolver::childRegionIds($districtCode);

            if ($ids !== []) {
                return $query->whereIn('umkm_locations.village_region_id', $ids);
            }
        }

        return $query->whereRaw('1 = 0');
    }

    private static function countDistinct(Builder $query): int
    {
        return (int) $query->distinct()->count('umkms.id');
    }

    private static function countMapped(Builder $query): int
    {
        if (! Schema::hasTable('umkm_locations')) {
            return 0;
        }

        if (Schema::hasColumn('umkm_locations', 'coordinate_status')) {
            return (int) $query
                ->where('umkm_locations.coordinate_status', 'terpetakan')
                ->distinct()
                ->count('umkms.id');
        }

        $lat = self::firstColumn('umkm_locations', ['latitude', 'lat']);
        $lng = self::firstColumn('umkm_locations', ['longitude', 'lng', 'lon']);

        if ($lat !== null && $lng !== null) {
            return (int) $query
                ->whereNotNull('umkm_locations.' . $lat)
                ->whereNotNull('umkm_locations.' . $lng)
                ->distinct()
                ->count('umkms.id');
        }

        return 0;
    }

    private static function dominantCategory(Builder $query, int $total): array
    {
        if ($total < 1) {
            return self::emptyDominantCategory();
        }

        foreach ([
            'dominantCategoryDirectReference',
            'dominantCategoryViaTypeReference',
            'dominantCategoryTextOnClassification',
            'dominantCategoryFromBaselineReference',
            'dominantCategoryTextOnBaseline',
            'dominantBusinessTypeFallback',
        ] as $method) {
            try {
                $result = self::$method(clone $query, $total);

                if ($result !== null && trim((string) $result['name']) !== '') {
                    return $result;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return self::emptyDominantCategory();
    }

    private static function dominantCategoryDirectReference(Builder $query, int $total): ?array
    {
        if (! Schema::hasTable('umkm_business_classifications') || ! Schema::hasTable('business_category_references')) {
            return null;
        }

        if (! Schema::hasColumn('umkm_business_classifications', 'umkm_id')) {
            return null;
        }

        $categoryId = self::firstColumn('umkm_business_classifications', [
            'business_category_reference_id',
            'business_category_id',
            'category_reference_id',
            'category_id',
        ]);

        $categoryName = self::firstColumn('business_category_references', [
            'name',
            'category_name',
            'business_category_name',
            'nama',
            'label',
            'title',
        ]);

        if ($categoryId === null || $categoryName === null) {
            return null;
        }

        $row = $query
            ->join('umkm_business_classifications as ubc', 'ubc.umkm_id', '=', 'umkms.id')
            ->join('business_category_references as bcr', 'bcr.id', '=', 'ubc.' . $categoryId)
            ->whereNotNull('ubc.' . $categoryId)
            ->selectRaw('bcr.' . self::wrap($categoryName) . ' as category_name, COUNT(DISTINCT umkms.id) as total_count')
            ->groupBy('bcr.' . $categoryName)
            ->orderByDesc('total_count')
            ->first();

        return self::dominantFromRow($row, $total);
    }

    private static function dominantCategoryViaTypeReference(Builder $query, int $total): ?array
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_type_references')
            || ! Schema::hasTable('business_category_references')
            || ! Schema::hasColumn('umkm_business_classifications', 'umkm_id')
        ) {
            return null;
        }

        $typeId = self::firstColumn('umkm_business_classifications', [
            'business_type_reference_id',
            'business_type_id',
            'type_reference_id',
            'type_id',
        ]);

        $typeCategoryId = self::firstColumn('business_type_references', [
            'business_category_reference_id',
            'business_category_id',
            'category_reference_id',
            'category_id',
        ]);

        $categoryName = self::firstColumn('business_category_references', [
            'name',
            'category_name',
            'business_category_name',
            'nama',
            'label',
            'title',
        ]);

        if ($typeId === null || $typeCategoryId === null || $categoryName === null) {
            return null;
        }

        $row = $query
            ->join('umkm_business_classifications as ubc', 'ubc.umkm_id', '=', 'umkms.id')
            ->join('business_type_references as btr', 'btr.id', '=', 'ubc.' . $typeId)
            ->join('business_category_references as bcr', 'bcr.id', '=', 'btr.' . $typeCategoryId)
            ->whereNotNull('ubc.' . $typeId)
            ->selectRaw('bcr.' . self::wrap($categoryName) . ' as category_name, COUNT(DISTINCT umkms.id) as total_count')
            ->groupBy('bcr.' . $categoryName)
            ->orderByDesc('total_count')
            ->first();

        return self::dominantFromRow($row, $total);
    }

    private static function dominantCategoryTextOnClassification(Builder $query, int $total): ?array
    {
        if (! Schema::hasTable('umkm_business_classifications') || ! Schema::hasColumn('umkm_business_classifications', 'umkm_id')) {
            return null;
        }

        $textColumn = self::firstColumn('umkm_business_classifications', [
            'category_name',
            'business_category_name',
            'kategori_usaha',
            'category',
            'local_category',
        ]);

        if ($textColumn === null) {
            return null;
        }

        $row = $query
            ->join('umkm_business_classifications as ubc', 'ubc.umkm_id', '=', 'umkms.id')
            ->whereNotNull('ubc.' . $textColumn)
            ->selectRaw('ubc.' . self::wrap($textColumn) . ' as category_name, COUNT(DISTINCT umkms.id) as total_count')
            ->groupBy('ubc.' . $textColumn)
            ->orderByDesc('total_count')
            ->first();

        return self::dominantFromRow($row, $total);
    }

    private static function dominantCategoryFromBaselineReference(Builder $query, int $total): ?array
    {
        if (! Schema::hasTable('umkm_baseline_profiles') || ! Schema::hasTable('business_category_references')) {
            return null;
        }

        if (! Schema::hasColumn('umkm_baseline_profiles', 'umkm_id')) {
            return null;
        }

        $categoryId = self::firstColumn('umkm_baseline_profiles', [
            'business_category_reference_id',
            'business_category_id',
            'category_reference_id',
            'category_id',
        ]);

        $categoryName = self::firstColumn('business_category_references', [
            'name',
            'category_name',
            'business_category_name',
            'nama',
            'label',
            'title',
        ]);

        if ($categoryId === null || $categoryName === null) {
            return null;
        }

        $row = $query
            ->join('umkm_baseline_profiles as ubp', 'ubp.umkm_id', '=', 'umkms.id')
            ->join('business_category_references as bcr', 'bcr.id', '=', 'ubp.' . $categoryId)
            ->whereNotNull('ubp.' . $categoryId)
            ->selectRaw('bcr.' . self::wrap($categoryName) . ' as category_name, COUNT(DISTINCT umkms.id) as total_count')
            ->groupBy('bcr.' . $categoryName)
            ->orderByDesc('total_count')
            ->first();

        return self::dominantFromRow($row, $total);
    }

    private static function dominantCategoryTextOnBaseline(Builder $query, int $total): ?array
    {
        if (! Schema::hasTable('umkm_baseline_profiles') || ! Schema::hasColumn('umkm_baseline_profiles', 'umkm_id')) {
            return null;
        }

        $textColumn = self::firstColumn('umkm_baseline_profiles', [
            'category_name',
            'business_category_name',
            'kategori_usaha',
            'category',
            'local_category',
        ]);

        if ($textColumn === null) {
            return null;
        }

        $row = $query
            ->join('umkm_baseline_profiles as ubp', 'ubp.umkm_id', '=', 'umkms.id')
            ->whereNotNull('ubp.' . $textColumn)
            ->selectRaw('ubp.' . self::wrap($textColumn) . ' as category_name, COUNT(DISTINCT umkms.id) as total_count')
            ->groupBy('ubp.' . $textColumn)
            ->orderByDesc('total_count')
            ->first();

        return self::dominantFromRow($row, $total);
    }

    private static function dominantBusinessTypeFallback(Builder $query, int $total): ?array
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_type_references')
            || ! Schema::hasColumn('umkm_business_classifications', 'umkm_id')
        ) {
            return null;
        }

        $typeId = self::firstColumn('umkm_business_classifications', [
            'business_type_reference_id',
            'business_type_id',
            'type_reference_id',
            'type_id',
        ]);

        $typeName = self::firstColumn('business_type_references', [
            'name',
            'type_name',
            'business_type_name',
            'nama',
            'label',
            'title',
        ]);

        if ($typeId === null || $typeName === null) {
            return null;
        }

        $row = $query
            ->join('umkm_business_classifications as ubc', 'ubc.umkm_id', '=', 'umkms.id')
            ->join('business_type_references as btr', 'btr.id', '=', 'ubc.' . $typeId)
            ->whereNotNull('ubc.' . $typeId)
            ->selectRaw('btr.' . self::wrap($typeName) . ' as category_name, COUNT(DISTINCT umkms.id) as total_count')
            ->groupBy('btr.' . $typeName)
            ->orderByDesc('total_count')
            ->first();

        return self::dominantFromRow($row, $total);
    }

    private static function dominantFromRow(mixed $row, int $total): ?array
    {
        if (! $row || ! isset($row->category_name)) {
            return null;
        }

        $name = trim((string) $row->category_name);
        $count = isset($row->total_count) ? (int) $row->total_count : 0;

        if ($name === '' || $count < 1) {
            return null;
        }

        return [
            'name' => $name,
            'count' => $count,
            'percent' => self::percent($count, $total),
        ];
    }

    private static function emptyDominantCategory(): array
    {
        return [
            'name' => 'Belum tersedia',
            'count' => 0,
            'percent' => 0.0,
        ];
    }

    private static function activeRegionCount(Builder $query, array $region): int
    {
        if (! Schema::hasTable('umkm_locations')) {
            return 0;
        }

        if ($region['scope'] === 'village') {
            return self::countDistinct(clone $query) > 0 ? 1 : 0;
        }

        foreach (['village_region_id', 'village_id', 'village_reference_id', 'village_code'] as $column) {
            if (Schema::hasColumn('umkm_locations', $column)) {
                return (int) $query
                    ->whereNotNull('umkm_locations.' . $column)
                    ->distinct()
                    ->count('umkm_locations.' . $column);
            }
        }

        return 0;
    }

    private static function coveragePercent(int $activeRegions, array $region): float
    {
        $denominator = 1;

        if ($region['scope'] === 'city') {
            $denominator = max(1, self::childCount($region['city_code']));
        } elseif ($region['scope'] === 'district') {
            $denominator = max(1, self::childCount((string) $region['district_code']));
        }

        return self::percent($activeRegions, $denominator);
    }

    private static function childCount(?string $parentCode): int
    {
        if ($parentCode === null || ! Schema::hasTable('regions')) {
            return 1;
        }

        $query = DB::table('regions');

        if (Schema::hasColumn('regions', 'parent_code')) {
            $query->where('parent_code', $parentCode);
        } elseif (Schema::hasColumn('regions', 'district_code')) {
            $query->where('district_code', $parentCode);
        } elseif (Schema::hasColumn('regions', 'code')) {
            $query->where('code', 'like', $parentCode . '.%');
        } else {
            return 1;
        }

        return max(1, (int) $query->count());
    }

    private static function aggregateCards(int $total, int $mapped, float $mappedPercent, array $dominant, int $activeRegions, float $coverage): array
    {
        return [
            'total_umkm' => [
                'key' => 'total_umkm',
                'label' => 'TOTAL UMKM',
                'value' => $total,
                'value_text' => self::formatNumber($total),
                'context' => 'Unit usaha tercatat',
                'badge' => 'Unit usaha tercatat',
                'percent_text' => $total > 0 ? self::formatPercent(100) . ' dari cakupan' : self::formatPercent(0) . ' dari cakupan',
                'progress_percent' => $total > 0 ? 100.0 : 0.0,
                'footer_label' => 'Data agregat database',
                'footer_value' => 'Public-safe',
            ],
            'mapped_umkm' => [
                'key' => 'mapped_umkm',
                'label' => 'TERPETAKAN',
                'value' => $mapped,
                'value_text' => self::formatNumber($mapped),
                'context' => 'Unit terpetakan',
                'badge' => self::formatPercent($mappedPercent) . ' dari cakupan',
                'percent_text' => self::formatPercent($mappedPercent) . ' dari cakupan',
                'progress_percent' => $mappedPercent,
                'footer_label' => 'Koordinat',
                'footer_value' => 'Valid',
            ],
            'dominant_category' => [
                'key' => 'dominant_category',
                'label' => 'KATEGORI DOMINAN',
                'value' => $dominant['name'],
                'value_text' => $dominant['name'],
                'context' => 'Kategori terbanyak',
                'badge' => self::formatPercent((float) $dominant['percent']) . ' dari cakupan',
                'percent_text' => self::formatPercent((float) $dominant['percent']) . ' dari cakupan',
                'progress_percent' => (float) $dominant['percent'],
                'footer_label' => 'Kategori terbanyak',
                'footer_value' => 'Insight',
            ],
            'active_regions' => [
                'key' => 'active_regions',
                'label' => 'WILAYAH AKTIF',
                'value' => $activeRegions,
                'value_text' => self::formatNumber($activeRegions),
                'context' => 'Kelurahan memiliki data',
                'badge' => 'Kelurahan memiliki data',
                'percent_text' => self::formatPercent($coverage) . ' wilayah tercakup',
                'progress_percent' => $coverage,
                'footer_label' => 'Cakupan wilayah',
                'footer_value' => self::formatPercent($coverage),
            ],
        ];
    }

    private static function areaDistribution(array $region): array
    {
        return [];
    }

    private static function firstColumn(string $table, array $candidates): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private static function percent(int|float $part, int|float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(((float) $part / (float) $total) * 100, 2);
    }

    private static function formatPercent(int|float $value): string
    {
        return number_format((float) $value, 2, ',', '.') . '%';
    }

    private static function formatNumber(int|float|string $value): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, 0, ',', '.');
    }

    private static function wrap(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}