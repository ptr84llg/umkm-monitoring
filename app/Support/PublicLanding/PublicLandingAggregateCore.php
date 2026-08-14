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
        $fields = self::fieldDistribution(clone $base, $total);
        $areas = self::areaDistribution($region);
        $validation = self::qualityFlagCount(clone $base);

        $cards = self::aggregateCards($total, $mapped, $mappedPercent, $dominant, $activeRegions, $coverage);
        $detailKey = self::cleanDetailCardKey($input['detail_card'] ?? null);

        $freshness = PublicLandingDataFreshness::latest();

        $payload = [
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
                'empty' => $total < 1,
                'total' => $total,
                'active' => $total,
                'validation' => $validation,
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
                'active_regions_label' => self::formatNumber($activeRegions),
                'aggregate_cards' => array_values($cards),
                'watched' => self::watchedLabel($region),
                'fields' => $fields,
                'areas' => $areas,
                'message' => $total < 1
                    ? 'Belum ada data agregat UMKM operasional untuk wilayah ini.'
                    : null,
            ],
            'chart' => self::chartPayload($region, $total, $mapped, $mappedPercent, $dominant, $activeRegions, $coverage, $areas),
            'trend_points' => self::trendPoints($total),
            'updated_at' => $freshness['label'],
            'updated_at_iso' => $freshness['iso'],
            'source_snapshot_id' => $freshness['snapshot_id'],
        ];

        if ($detailKey !== null) {
            $payload['detail_card'] = self::detailCard(
                $detailKey,
                $region,
                $cards,
                $total,
                $mapped,
                $mappedPercent,
                $dominant,
                $activeRegions,
                $coverage
            );
        }

        return $payload;
    }

    private static function baseQuery(): Builder
    {
        $query = DB::table('umkms');
        self::applyPublicStatusFilter($query);

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

        $lat = self::firstColumn('umkm_locations', ['latitude', 'lat']);
        $lng = self::firstColumn('umkm_locations', ['longitude', 'lng', 'lon']);

        if (Schema::hasColumn('umkm_locations', 'coordinate_status')) {
            $query->where('umkm_locations.coordinate_status', 'terpetakan');

            if ($lat !== null) {
                $query->whereNotNull('umkm_locations.' . $lat);
            }

            if ($lng !== null) {
                $query->whereNotNull('umkm_locations.' . $lng);
            }

            return (int) $query->distinct()->count('umkms.id');
        }

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
                'label' => 'UMKM OPERASIONAL',
                'value' => $total,
                'value_text' => self::formatNumber($total),
                'context' => 'Unit usaha operasional',
                'badge' => 'UMKM operasional',
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

    private static function cleanDetailCardKey(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $key = strtolower(trim((string) $value));
        $allowed = ['total_umkm', 'mapped_umkm', 'dominant_category', 'active_regions'];

        return in_array($key, $allowed, true) ? $key : null;
    }

    private static function detailCard(
        ?string $key,
        array $region,
        array $cards,
        int $total,
        int $mapped,
        float $mappedPercent,
        array $dominant,
        int $activeRegions,
        float $coverage
    ): ?array {
        if ($key === null || ! isset($cards[$key])) {
            return null;
        }

        $unmapped = max(0, $total - $mapped);
        $summary = match ($key) {
            'total_umkm' => [
                ['label' => 'UMKM operasional', 'value' => self::formatNumber($total)],
                ['label' => 'Unit terpetakan', 'value' => self::formatNumber($mapped)],
                ['label' => 'Belum terpetakan', 'value' => self::formatNumber($unmapped)],
                ['label' => 'Kategori dominan', 'value' => (string) ($dominant['name'] ?? 'Belum tersedia')],
            ],
            'mapped_umkm' => [
                ['label' => 'Unit terpetakan', 'value' => self::formatNumber($mapped)],
                ['label' => 'Belum terpetakan', 'value' => self::formatNumber($unmapped)],
                ['label' => 'Persentase terpetakan', 'value' => self::formatPercent($mappedPercent)],
                ['label' => 'Total cakupan', 'value' => self::formatNumber($total)],
            ],
            'dominant_category' => [
                ['label' => 'Kategori dominan', 'value' => (string) ($dominant['name'] ?? 'Belum tersedia')],
                ['label' => 'Jumlah kategori dominan', 'value' => self::formatNumber((int) ($dominant['count'] ?? 0))],
                ['label' => 'Persentase kategori dominan', 'value' => self::formatPercent((float) ($dominant['percent'] ?? 0))],
                ['label' => 'Total cakupan', 'value' => self::formatNumber($total)],
            ],
            'active_regions' => [
                ['label' => 'Wilayah aktif', 'value' => self::formatNumber($activeRegions)],
                ['label' => 'Cakupan wilayah', 'value' => self::formatPercent($coverage)],
                ['label' => 'UMKM operasional', 'value' => self::formatNumber($total)],
                ['label' => 'Scope aktif', 'value' => strtoupper((string) ($region['scope'] ?? 'city'))],
            ],
            default => [],
        };

        $sections = [[
            'title' => 'Ringkasan agregat',
            'items' => array_map(function ($item) {
                return [
                    'label' => $item['label'] ?? '-',
                    'value' => $item['value'] ?? '-',
                    'meta' => 'Public-safe',
                    'progress_percent' => 100,
                ];
            }, $summary),
            'empty' => 'Ringkasan agregat belum tersedia.',
        ]];

        $titles = [
            'total_umkm' => 'Detail UMKM Operasional',
            'mapped_umkm' => 'Detail Keterpetaan UMKM',
            'dominant_category' => 'Detail Kategori Dominan',
            'active_regions' => 'Detail Wilayah Aktif',
        ];

        return [
            'key' => $key,
            'title' => $titles[$key] ?? 'Detail Agregat',
            'subtitle' => (string) ($region['context_label'] ?? 'Kota Lubuklinggau, Sumatera Selatan'),
            'card' => $cards[$key],
            'summary' => $summary,
            'sections' => $sections,
            'public_safe_note' => 'Detail yang ditampilkan bersifat agregat public-safe dan tidak membuka data pemilik, kontak, alamat detail, koordinat presisi, foto detail, atau payload mentah.',
        ];
    }

    private static function fieldDistribution(Builder $query, int $total): array
    {
        if (
            $total < 1
            || ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_category_references')
            || ! Schema::hasColumn('umkm_business_classifications', 'umkm_id')
        ) {
            return [];
        }

        $categoryId = self::firstColumn('umkm_business_classifications', [
            'business_category_id',
            'business_category_reference_id',
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
            return [];
        }

        return $query
            ->join('umkm_business_classifications as field_classifications', 'field_classifications.umkm_id', '=', 'umkms.id')
            ->join('business_category_references as field_categories', 'field_categories.id', '=', 'field_classifications.' . $categoryId)
            ->whereNotNull('field_classifications.' . $categoryId)
            ->selectRaw('field_categories.' . self::wrap($categoryName) . ' as category_name, COUNT(DISTINCT umkms.id) as total_count')
            ->groupBy('field_categories.' . $categoryName)
            ->orderByDesc('total_count')
            ->limit(3)
            ->get()
            ->map(function (object $row) use ($total): array {
                $count = (int) ($row->total_count ?? 0);

                return [
                    'name' => trim((string) ($row->category_name ?? 'Belum tersedia')),
                    'count' => $count,
                    'percent' => self::percent($count, $total),
                ];
            })
            ->filter(fn (array $item): bool => $item['name'] !== '' && $item['count'] > 0)
            ->values()
            ->all();
    }

    private static function qualityFlagCount(Builder $query): int
    {
        if (
            ! Schema::hasTable('umkm_data_quality_flags')
            || ! Schema::hasColumn('umkm_data_quality_flags', 'umkm_id')
        ) {
            return 0;
        }

        $query->join('umkm_data_quality_flags as public_quality_flags', 'public_quality_flags.umkm_id', '=', 'umkms.id');

        if (Schema::hasColumn('umkm_data_quality_flags', 'status')) {
            $query->where('public_quality_flags.status', 'open');
        }

        return (int) $query
            ->distinct()
            ->count('umkms.id');
    }

    private static function watchedLabel(array $region): string
    {
        if (($region['scope'] ?? 'city') === 'village') {
            return '1 Kelurahan';
        }

        if (($region['scope'] ?? 'city') === 'district') {
            $total = self::childCount((string) ($region['district_code'] ?? ''));

            return $total > 0 ? self::formatNumber($total) . ' Kelurahan' : 'Kelurahan terpantau';
        }

        $total = self::childCount((string) ($region['city_code'] ?? config('umkm.landing_region.city_code', '16.73')));

        return $total > 0 ? self::formatNumber($total) . ' Kecamatan' : 'Kecamatan terpantau';
    }

    private static function chartPayload(
        array $region,
        int $total,
        int $mapped,
        float $mappedPercent,
        array $dominant,
        int $activeRegions,
        float $coverage,
        array $areas
    ): array {
        $chartAreas = array_slice($areas, 0, 5);

        if ($chartAreas === []) {
            $chartAreas = [[
                'name' => (string) ($region['context_label'] ?? 'Kota Lubuklinggau'),
                'count' => $total,
                'percent' => $total > 0 ? 100 : 0,
            ]];
        }

        return [
            'title' => 'Preview UMKM Operasional',
            'subtitle' => 'Agregat public-safe untuk ' . (string) ($region['context_label'] ?? 'Kota Lubuklinggau'),
            'labels' => array_map(fn (array $area): string => (string) ($area['name'] ?? 'Wilayah'), $chartAreas),
            'unit_label' => 'Jumlah UMKM Operasional',
            'percent_label' => 'Persentase Cakupan',
            'unit_data' => array_map(fn (array $area): int => (int) ($area['count'] ?? 0), $chartAreas),
            'percent_data' => array_map(fn (array $area): int => max(0, min(100, (int) round((float) ($area['percent'] ?? 0)))), $chartAreas),
            'summary_one' => self::formatNumber($total) . ' UMKM operasional',
            'summary_two' => self::formatPercent($mappedPercent) . ' terpetakan',
            'summary_three' => self::formatNumber($activeRegions) . ' wilayah aktif',
            'dominant_category' => (string) ($dominant['name'] ?? 'Belum tersedia'),
            'coverage_percent' => self::formatPercent($coverage),
        ];
    }

    private static function trendPoints(int $total): array
    {
        $ratios = [0.62, 0.68, 0.74, 0.82, 1.00];
        $classes = ['point-01', 'point-02', 'point-03', 'point-04', 'point-05'];

        return collect($ratios)
            ->map(fn (float $ratio, int $index): array => [
                'class' => $classes[$index] ?? 'point-01',
                'value' => self::formatNumber((int) round($total * $ratio)),
            ])
            ->values()
            ->all();
    }

    private static function areaDistribution(array $region): array
    {
        if (! Schema::hasTable('umkm_locations') || ! Schema::hasTable('regions')) {
            return [];
        }

        $base = self::baseQuery();
        self::applyRegionFilter($base, $region);
        $total = self::countDistinct(clone $base);

        if ($total < 1) {
            return [];
        }

        if (($region['scope'] ?? 'city') === 'village') {
            $dominant = self::dominantCategory(clone $base, $total);

            return [[
                'name' => (string) ($region['village_name'] ?? $region['context_label'] ?? 'Wilayah terpilih'),
                'count' => $total,
                'sector' => $dominant['name'] !== 'Belum tersedia' ? $dominant['name'] : 'Klasifikasi UMKM',
                'percent' => 100,
            ]];
        }

        $targetLevel = ($region['scope'] ?? 'city') === 'city' ? 'district' : 'village';
        $rows = self::areaRowsForTarget(clone $base, $targetLevel);

        return collect($rows)
            ->map(function (object $row) use ($targetLevel, $total): array {
                $count = (int) ($row->total_count ?? 0);
                $areaQuery = self::baseQuery();
                self::applyLocationCodeOrId($areaQuery, $targetLevel, (string) $row->code);
                $dominant = self::dominantCategory($areaQuery, $count);

                return [
                    'name' => (string) ($row->name ?? 'Wilayah'),
                    'count' => $count,
                    'sector' => $dominant['name'] !== 'Belum tersedia' ? $dominant['name'] : 'Klasifikasi UMKM',
                    'percent' => max(1, min(100, (int) round(($count / $total) * 100))),
                ];
            })
            ->values()
            ->all();
    }

    private static function areaRowsForTarget(Builder $query, string $targetLevel): array
    {
        $codeColumn = self::firstColumn('umkm_locations', [$targetLevel . '_code']);

        if ($codeColumn !== null && Schema::hasColumn('regions', 'code')) {
            $query->join('regions as area_regions', 'area_regions.code', '=', 'umkm_locations.' . $codeColumn);
        } else {
            $idColumns = $targetLevel === 'district'
                ? ['district_region_id', 'district_id', 'district_reference_id']
                : ['village_region_id', 'village_id', 'village_reference_id'];
            $idColumn = self::firstColumn('umkm_locations', $idColumns);

            if ($idColumn === null || ! Schema::hasColumn('regions', 'id')) {
                return [];
            }

            $query->join('regions as area_regions', 'area_regions.id', '=', 'umkm_locations.' . $idColumn);
        }

        if (Schema::hasColumn('regions', 'level')) {
            $query->where('area_regions.level', $targetLevel);
        }

        if (Schema::hasColumn('regions', 'city_code')) {
            $query->where('area_regions.city_code', (string) config('umkm.landing_region.city_code', '16.73'));
        }

        return $query
            ->whereNotNull('area_regions.name')
            ->selectRaw('area_regions.code as code, area_regions.name as name, COUNT(DISTINCT umkms.id) as total_count')
            ->groupBy('area_regions.code', 'area_regions.name')
            ->orderByDesc('total_count')
            ->limit(3)
            ->get()
            ->all();
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

    private static function applyPublicStatusFilter(Builder $query): Builder
    {
        if (Schema::hasColumn('umkms', 'status_data')) {
            $statuses = self::publicStatuses();

            if ($statuses !== []) {
                $query->whereIn('umkms.status_data', $statuses);
            }
        }

        self::applySourceActiveGuard($query);

        return $query;
    }

    private static function applySourceActiveGuard(Builder $query): void
    {
        if (! Schema::hasColumn('umkms', 'source_active')) {
            return;
        }

        if (! Schema::hasColumn('umkms', 'source_system')) {
            $query->where('umkms.source_active', 1);

            return;
        }

        $query->where(function (Builder $guard): void {
            $guard->whereNull('umkms.source_system')
                ->orWhere('umkms.source_system', '<>', 'LSS')
                ->orWhere('umkms.source_active', 1);
        });
    }

    private static function publicStatuses(): array
    {
        $statuses = array_values(array_filter(array_map(
            fn ($status) => trim((string) $status),
            (array) config('umkm.data.public_statuses', ['resmi', 'terbatas'])
        )));

        return $statuses === [] ? ['resmi', 'terbatas'] : $statuses;
    }
}
