<?php

namespace App\Support\PublicLanding;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PublicLandingMetricQuery
{
    public static function payload(array $input = []): array
    {
        $context = self::resolveContext($input);
        $base = self::baseOperationalQuery($context);

        $total = self::countDistinctUmkm($base);
        $mapped = self::countDistinctUmkm(
            self::cloneQuery($base)
                ->whereNotNull('umkm_locations.latitude')
                ->whereNotNull('umkm_locations.longitude')
        );

        $mappedPercent = self::percent($mapped, $total);
        $activeRegions = self::activeVillageCount($context, $base, $total);
        $regionDenominator = self::villageDenominator($context);
        $coverage = self::percent($activeRegions, $regionDenominator);
        $dominant = self::dominantCategory($base, $total);

        $cards = self::aggregateCards($total, $mapped, $mappedPercent, $dominant, $activeRegions, $coverage);

        $payload = [
            'scope' => $context['scope'],
            'region' => $context['region'],
            'selection' => $context['selection'],
            'context_label' => $context['label'],
            'summary' => [
                'total' => $total,
                'mapped' => $mapped,
                'unmapped' => max(0, $total - $mapped),
                'mapped_percent' => $mappedPercent,
                'active_regions' => $activeRegions,
                'region_denominator' => $regionDenominator,
                'coverage_percent' => $coverage,
                'dominant_category' => $dominant,
            ],
            'aggregate_cards' => array_values($cards),
            'aggregate_card_map' => $cards,
            'preview' => self::preview($total, $mapped, $mappedPercent, $activeRegions, $regionDenominator, $coverage, $dominant),
            'analytics' => self::analyticsPayload($context, self::cloneQuery($base), $total),
            'fields' => self::fields($total, $mappedPercent, $coverage, $dominant),
            'areas' => [],
            'watched' => [
                'label' => 'Wilayah aktif',
                'value' => $context['label'],
            ],
            'active' => [
                'label' => 'Konteks aktif',
                'value' => $context['label'],
            ],
            'validation' => [
                'status' => 'aman',
                'message' => 'Data agregat aman untuk publik.',
            ],
            'chart' => self::chart($total, $mapped, $activeRegions, $coverage),
            'trend_points' => [['label' => 'Saat ini', 'value' => $total]],
            'empty' => $total < 1,
            'empty_message' => $total < 1 ? 'Data belum tersedia pada wilayah ini.' : null,
            'updated_at' => now()->toIso8601String(),
        ];

        $detailCard = self::cleanDetailCardKey((string) ($input['detail_card'] ?? ''));
        if ($detailCard !== null) {
            $payload['detail_card'] = self::detailCard($detailCard, $context, $cards, $total, $mapped, $mappedPercent, $dominant, $activeRegions, $coverage, $regionDenominator);
        }

        return $payload;
    }

    private static function resolveContext(array $input): array
    {
        $lockedProvinceCode = trim((string) config('umkm.landing_region.province_code', '16'));
        $lockedProvinceName = trim((string) config('umkm.landing_region.province_name', 'Sumatera Selatan'));
        $lockedCityCode = trim((string) config('umkm.landing_region.city_code', '16.73'));
        $lockedCityName = trim((string) config('umkm.landing_region.city_name', 'Kota Lubuklinggau'));

        $provinceCode = $lockedProvinceCode !== '' ? $lockedProvinceCode : trim((string) ($input['province_code'] ?? ''));
        $cityCode = $lockedCityCode !== '' ? $lockedCityCode : trim((string) ($input['city_code'] ?? ''));
        $districtCode = trim((string) ($input['district_code'] ?? ''));
        $villageCode = trim((string) ($input['village_code'] ?? ''));

        if ($cityCode !== '') {
            if ($districtCode !== '' && ! str_starts_with($districtCode, $cityCode . '.')) {
                $districtCode = '';
            }

            if ($villageCode !== '' && ! str_starts_with($villageCode, $cityCode . '.')) {
                $villageCode = '';
            }
        }

        $scope = trim((string) ($input['scope'] ?? config('umkm.landing_region.default_scope', 'city')));

        if ($villageCode !== '') {
            $scope = 'village';
        } elseif ($districtCode !== '') {
            $scope = 'district';
        } elseif (! in_array($scope, ['city', 'district', 'village'], true)) {
            $scope = 'city';
        }

        $province = self::regionByCode($provinceCode);
        $city = self::regionByCode($cityCode);
        $district = $districtCode !== '' ? self::regionByCode($districtCode) : null;
        $village = $villageCode !== '' ? self::regionByCode($villageCode) : null;

        if ($scope === 'district' && ! $district) {
            $scope = 'city';
            $districtCode = '';
        }

        if ($scope === 'village' && ! $village) {
            $scope = $district ? 'district' : 'city';
            $villageCode = '';
        }

        $label = match ($scope) {
            'village' => 'Kelurahan ' . (string) ($village['name'] ?? $villageCode) . ', Kota Lubuk Linggau, Sumatera Selatan',
            'district' => 'Kecamatan ' . (string) ($district['name'] ?? $districtCode) . ', Kota Lubuk Linggau, Sumatera Selatan',
            default => ($lockedCityName !== '' ? $lockedCityName : (string) ($city['name'] ?? 'Kota Lubuklinggau')) . ', ' . ($lockedProvinceName !== '' ? $lockedProvinceName : (string) ($province['name'] ?? 'Sumatera Selatan')),
        };

        return [
            'scope' => $scope,
            'label' => $label,
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'village' => $village,
            'region' => [
                'scope' => $scope,
                'province_code' => $provinceCode,
                'province_name' => $lockedProvinceName !== '' ? $lockedProvinceName : (string) ($province['name'] ?? ''),
                'city_code' => $cityCode,
                'city_name' => $lockedCityName !== '' ? $lockedCityName : (string) ($city['name'] ?? ''),
                'district_code' => $districtCode,
                'district_name' => (string) ($district['name'] ?? ''),
                'village_code' => $villageCode,
                'village_name' => (string) ($village['name'] ?? ''),
            ],
            'selection' => [
                'scope' => $scope,
                'province_code' => $provinceCode,
                'city_code' => $cityCode,
                'district_code' => $districtCode,
                'village_code' => $villageCode,
            ],
        ];
    }

    private static function regionByCode(string $code): ?array
    {
        if ($code === '' || ! Schema::hasTable('regions') || ! Schema::hasColumn('regions', 'code')) {
            return null;
        }

        $nameColumn = Schema::hasColumn('regions', 'name') ? 'name' : (Schema::hasColumn('regions', 'region_name') ? 'region_name' : 'code');

        $row = DB::table('regions')
            ->select(['id', 'code', DB::raw('`' . $nameColumn . '` as name')])
            ->where('code', $code)
            ->first();

        return $row ? [
            'id' => (int) $row->id,
            'code' => (string) $row->code,
            'name' => (string) $row->name,
        ] : null;
    }

    private static function baseOperationalQuery(array $context): Builder
    {
        $query = DB::table('umkms')
            ->leftJoin('umkm_locations', 'umkm_locations.umkm_id', '=', 'umkms.id')
            ->whereIn('umkms.status_data', self::operationalStatuses());

        $cityId = (int) ($context['city']['id'] ?? 0);
        $districtId = (int) ($context['district']['id'] ?? 0);
        $villageId = (int) ($context['village']['id'] ?? 0);

        if ($context['scope'] === 'village' && $villageId > 0) {
            $query->where('umkm_locations.village_region_id', $villageId);
        } elseif ($context['scope'] === 'district' && $districtId > 0) {
            $query->where('umkm_locations.district_region_id', $districtId);
        } elseif ($cityId > 0) {
            $query->where('umkm_locations.city_region_id', $cityId);
        }

        return $query;
    }

    private static function operationalStatuses(): array
    {
        $statuses = config('umkm.data.operational_statuses', ['resmi', 'terbatas']);
        $items = is_array($statuses) ? $statuses : explode(',', (string) $statuses);

        return array_values(array_filter(array_map('trim', $items)));
    }

    private static function cloneQuery(Builder $query): Builder
    {
        return clone $query;
    }

    private static function countDistinctUmkm(Builder $query): int
    {
        return (int) self::cloneQuery($query)->distinct()->count('umkms.id');
    }

    private static function activeVillageCount(array $context, Builder $base, int $total): int
    {
        if ($context['scope'] === 'village') {
            return $total > 0 ? 1 : 0;
        }

        return (int) self::cloneQuery($base)
            ->whereNotNull('umkm_locations.village_region_id')
            ->distinct()
            ->count('umkm_locations.village_region_id');
    }

    private static function villageDenominator(array $context): int
    {
        if (! Schema::hasTable('regions')) {
            return 1;
        }

        if ($context['scope'] === 'village') {
            return 1;
        }

        $query = DB::table('regions');

        if (Schema::hasColumn('regions', 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn('regions', 'level')) {
            $query->whereIn(DB::raw('LOWER(`level`)'), ['village', 'kelurahan', 'desa']);
        }

        if ($context['scope'] === 'district') {
            $districtCode = (string) ($context['district']['code'] ?? '');
            if ($districtCode !== '') {
                if (Schema::hasColumn('regions', 'parent_code')) {
                    $query->where('parent_code', $districtCode);
                } elseif (Schema::hasColumn('regions', 'code')) {
                    $query->where('code', 'like', $districtCode . '.%')
                        ->whereRaw("LENGTH(`code`) - LENGTH(REPLACE(`code`, '.', '')) = 3");
                }
            }
        } else {
            $cityCode = (string) ($context['city']['code'] ?? config('umkm.landing_region.city_code', '16.73'));
            if ($cityCode !== '') {
                if (Schema::hasColumn('regions', 'city_code')) {
                    $query->where('city_code', $cityCode);
                } elseif (Schema::hasColumn('regions', 'code')) {
                    $query->where('code', 'like', $cityCode . '.%.%')
                        ->whereRaw("LENGTH(`code`) - LENGTH(REPLACE(`code`, '.', '')) = 3");
                }
            }
        }

        return max(1, (int) $query->count());
    }

    private static function dominantCategory(Builder $base, int $total): array
    {
        if (! Schema::hasTable('umkm_business_classifications') || ! Schema::hasTable('business_category_references')) {
            return ['name' => 'Belum tersedia', 'count' => 0, 'percent' => 0.0];
        }

        try {
            $query = self::cloneQuery($base)
                ->join('umkm_business_classifications as classifications', 'classifications.umkm_id', '=', 'umkms.id')
                ->join('business_category_references as categories', 'categories.id', '=', 'classifications.business_category_id');

            if (Schema::hasColumn('umkm_business_classifications', 'is_primary')) {
                $query->where('classifications.is_primary', true);
            }

            if (Schema::hasColumn('business_category_references', 'is_active')) {
                $query->where('categories.is_active', true);
            }

            $row = $query
                ->select('categories.name as name', DB::raw('COUNT(DISTINCT umkms.id) as total_count'))
                ->groupBy('categories.name')
                ->orderByDesc('total_count')
                ->first();

            if (! $row) {
                return ['name' => 'Belum tersedia', 'count' => 0, 'percent' => 0.0];
            }

            $count = (int) $row->total_count;

            return [
                'name' => (string) $row->name,
                'count' => $count,
                'percent' => self::percent($count, $total),
            ];
        } catch (\Throwable) {
            return ['name' => 'Belum tersedia', 'count' => 0, 'percent' => 0.0];
        }
    }

    private static function aggregateCards(int $total, int $mapped, float $mappedPercent, array $dominant, int $activeRegions, float $coverage): array
    {
        return [
            'total_umkm' => [
                'key' => 'total_umkm',
                'label' => 'UMKM OPERASIONAL',
                'value' => $total,
                'value_text' => self::formatNumber($total),
                'percent_text' => $total > 0 ? '100,00% dari cakupan' : '0,00% dari cakupan',
                'progress_percent' => $total > 0 ? 100.0 : 0.0,
                'context' => 'Unit usaha operasional',
                'footer_label' => 'Data agregat',
                'footer_value' => 'Aman untuk publik',
            ],
            'mapped_umkm' => [
                'key' => 'mapped_umkm',
                'label' => 'TERPETAKAN',
                'value' => $mapped,
                'value_text' => self::formatNumber($mapped),
                'percent_text' => self::formatPercent($mappedPercent) . ' dari cakupan',
                'progress_percent' => $mappedPercent,
                'context' => 'Unit terpetakan',
                'footer_label' => 'Titik lokasi',
                'footer_value' => 'Tersedia',
            ],
            'dominant_category' => [
                'key' => 'dominant_category',
                'label' => 'KATEGORI DOMINAN',
                'value' => $dominant['name'],
                'value_text' => (string) $dominant['name'],
                'percent_text' => self::formatPercent((float) $dominant['percent']) . ' dari cakupan',
                'progress_percent' => (float) $dominant['percent'],
                'context' => 'Kategori terbanyak',
                'footer_label' => 'Kategori terbanyak',
                'footer_value' => 'Ringkasan',
            ],
            'active_regions' => [
                'key' => 'active_regions',
                'label' => 'WILAYAH AKTIF',
                'value' => $activeRegions,
                'value_text' => self::formatNumber($activeRegions),
                'percent_text' => self::formatPercent($coverage) . ' wilayah tercakup',
                'progress_percent' => $coverage,
                'context' => 'Kelurahan memiliki data',
                'footer_label' => 'Cakupan wilayah',
                'footer_value' => self::formatPercent($coverage),
            ],
        ];
    }

    private static function analyticsPayload(array $context, Builder $base, int $total): array
    {
        $businessStructure = self::businessStructureAnalytics($base, $total);
        $marketing = self::marketingAnalytics($base, $total);
        $readiness = self::dataReadinessAnalytics($base, $total);
        $areaComparison = self::areaComparisonAnalytics($context, $base);

        return [
            'context' => self::analyticsContext($context),
            'business_structure' => $businessStructure,
            'marketing' => $marketing,
            'data_readiness' => $readiness,
            'area_comparison' => $areaComparison,
            'decision_notes' => [
                'recommendations' => self::decisionRecommendations($readiness, $marketing),
            ],
        ];
    }

    private static function analyticsContext(array $context): array
    {
        return [
            'scope' => (string) ($context['scope'] ?? 'city'),
            'label' => (string) ($context['label'] ?? 'Kota Lubuklinggau'),
            'city' => [
                'code' => (string) ($context['region']['city_code'] ?? config('umkm.landing_region.city_code', '16.73')),
                'name' => (string) ($context['region']['city_name'] ?? config('umkm.landing_region.city_name', 'Kota Lubuklinggau')),
            ],
            'district' => ((string) ($context['region']['district_code'] ?? '')) !== '' ? [
                'code' => (string) ($context['region']['district_code'] ?? ''),
                'name' => (string) ($context['region']['district_name'] ?? ''),
            ] : null,
            'village' => ((string) ($context['region']['village_code'] ?? '')) !== '' ? [
                'code' => (string) ($context['region']['village_code'] ?? ''),
                'name' => (string) ($context['region']['village_name'] ?? ''),
            ] : null,
        ];
    }

    private static function businessStructureAnalytics(Builder $base, int $total): array
    {
        $categories = self::businessCategoryAnalyticsRows($base, $total);
        $types = self::businessTypeAnalyticsRows($base, $total);

        return [
            'total_umkm' => $total,
            'dominant_category' => $categories[0]['name'] ?? 'Belum tersedia',
            'dominant_type' => $types[0]['name'] ?? 'Belum tersedia',
            'categories' => $categories,
            'types' => $types,
        ];
    }

    private static function businessCategoryAnalyticsRows(Builder $base, int $total): array
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_category_references')
            || ! Schema::hasColumn('umkm_business_classifications', 'umkm_id')
            || ! Schema::hasColumn('umkm_business_classifications', 'business_category_id')
        ) {
            return [];
        }

        $query = self::cloneQuery($base)
            ->join('umkm_business_classifications as analytics_classifications', 'analytics_classifications.umkm_id', '=', 'umkms.id')
            ->join('business_category_references as analytics_categories', 'analytics_categories.id', '=', 'analytics_classifications.business_category_id');

        self::applyClassificationGuards($query, 'analytics_classifications');
        self::applyReferenceActiveGuard($query, 'business_category_references', 'analytics_categories');

        return $query
            ->select(
                'analytics_categories.id',
                'analytics_categories.name',
                DB::raw('COUNT(DISTINCT umkms.id) as total_count')
            )
            ->groupBy('analytics_categories.id', 'analytics_categories.name')
            ->orderByDesc('total_count')
            ->orderBy('analytics_categories.name')
            ->limit(12)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) ($row->name ?: 'Belum tersedia'),
                'total' => (int) $row->total_count,
                'percentage' => self::percent((int) $row->total_count, $total),
            ])
            ->values()
            ->all();
    }

    private static function businessTypeAnalyticsRows(Builder $base, int $total): array
    {
        if (
            ! Schema::hasTable('umkm_business_classifications')
            || ! Schema::hasTable('business_type_references')
            || ! Schema::hasTable('business_category_references')
            || ! Schema::hasColumn('umkm_business_classifications', 'umkm_id')
            || ! Schema::hasColumn('umkm_business_classifications', 'business_type_id')
            || ! Schema::hasColumn('umkm_business_classifications', 'business_category_id')
        ) {
            return [];
        }

        $query = self::cloneQuery($base)
            ->join('umkm_business_classifications as analytics_type_classifications', 'analytics_type_classifications.umkm_id', '=', 'umkms.id')
            ->join('business_type_references as analytics_types', 'analytics_types.id', '=', 'analytics_type_classifications.business_type_id')
            ->leftJoin('business_category_references as analytics_type_categories', 'analytics_type_categories.id', '=', 'analytics_type_classifications.business_category_id');

        self::applyClassificationGuards($query, 'analytics_type_classifications');
        self::applyReferenceActiveGuard($query, 'business_type_references', 'analytics_types');
        self::applyReferenceActiveGuard($query, 'business_category_references', 'analytics_type_categories');

        return $query
            ->select(
                'analytics_types.id',
                'analytics_types.name',
                DB::raw("COALESCE(analytics_type_categories.name, 'Belum tersedia') as category_name"),
                DB::raw('COUNT(DISTINCT umkms.id) as total_count')
            )
            ->groupBy('analytics_types.id', 'analytics_types.name', 'analytics_type_categories.name')
            ->orderByDesc('total_count')
            ->orderBy('analytics_types.name')
            ->limit(15)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) ($row->name ?: 'Belum tersedia'),
                'category_name' => (string) ($row->category_name ?: 'Belum tersedia'),
                'total' => (int) $row->total_count,
                'percentage' => self::percent((int) $row->total_count, $total),
            ])
            ->values()
            ->all();
    }

    private static function marketingAnalytics(Builder $base, int $total): array
    {
        if (
            ! Schema::hasTable('umkm_baseline_profiles')
            || ! Schema::hasTable('marketing_method_references')
            || ! Schema::hasColumn('umkm_baseline_profiles', 'umkm_id')
            || ! Schema::hasColumn('umkm_baseline_profiles', 'marketing_method_id')
        ) {
            return [
                'dominant_method' => 'Belum tersedia',
                'methods' => [],
            ];
        }

        $query = self::cloneQuery($base)
            ->leftJoin('umkm_baseline_profiles as analytics_baseline', 'analytics_baseline.umkm_id', '=', 'umkms.id')
            ->leftJoin('marketing_method_references as analytics_marketing_methods', 'analytics_marketing_methods.id', '=', 'analytics_baseline.marketing_method_id');

        self::applyReferenceActiveGuard($query, 'marketing_method_references', 'analytics_marketing_methods');

        $rows = $query
            ->select(
                DB::raw("COALESCE(analytics_marketing_methods.name, 'Belum tersedia') as name"),
                DB::raw('COUNT(DISTINCT umkms.id) as total_count')
            )
            ->groupBy(DB::raw("COALESCE(analytics_marketing_methods.name, 'Belum tersedia')"))
            ->orderByDesc('total_count')
            ->orderBy('name')
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) ($row->name ?: 'Belum tersedia'),
                'total' => (int) $row->total_count,
                'percentage' => self::percent((int) $row->total_count, $total),
            ])
            ->values()
            ->all();

        return [
            'dominant_method' => $rows[0]['name'] ?? 'Belum tersedia',
            'methods' => $rows,
        ];
    }

    private static function dataReadinessAnalytics(Builder $base, int $total): array
    {
        $mapped = self::countDistinctUmkm(
            self::cloneQuery($base)
                ->where('umkm_locations.coordinate_status', 'terpetakan')
                ->whereNotNull('umkm_locations.latitude')
                ->whereNotNull('umkm_locations.longitude')
        );

        $needsValidation = Schema::hasColumn('umkm_locations', 'coordinate_status')
            ? self::countDistinctUmkm(
                self::cloneQuery($base)->where('umkm_locations.coordinate_status', 'perlu_validasi')
            )
            : 0;

        $missingDistrict = Schema::hasColumn('umkm_locations', 'district_region_id')
            ? self::countDistinctUmkm(self::cloneQuery($base)->whereNull('umkm_locations.district_region_id'))
            : 0;

        $missingVillage = Schema::hasColumn('umkm_locations', 'village_region_id')
            ? self::countDistinctUmkm(self::cloneQuery($base)->whereNull('umkm_locations.village_region_id'))
            : 0;

        return [
            'location' => [
                'total_umkm' => $total,
                'mapped_total' => $mapped,
                'unmapped_total' => max(0, $total - $mapped),
                'needs_validation_total' => $needsValidation,
                'missing_district_total' => $missingDistrict,
                'missing_village_total' => $missingVillage,
                'mapped_percentage' => self::percent($mapped, $total),
            ],
            'quality_notes' => self::qualityNoteAnalyticsRows($base),
        ];
    }

    private static function qualityNoteAnalyticsRows(Builder $base): array
    {
        if (
            ! Schema::hasTable('umkm_data_quality_flags')
            || ! Schema::hasColumn('umkm_data_quality_flags', 'umkm_id')
        ) {
            return [];
        }

        $query = self::cloneQuery($base)
            ->join('umkm_data_quality_flags as analytics_quality_flags', 'analytics_quality_flags.umkm_id', '=', 'umkms.id');

        if (Schema::hasColumn('umkm_data_quality_flags', 'status')) {
            $query->where('analytics_quality_flags.status', 'open');
        }

        return $query
            ->select(
                DB::raw("COALESCE(analytics_quality_flags.flag_group, 'general') as flag_group"),
                DB::raw("COALESCE(analytics_quality_flags.severity, 'info') as severity"),
                DB::raw('COUNT(DISTINCT analytics_quality_flags.umkm_id) as total_count')
            )
            ->groupBy(
                DB::raw("COALESCE(analytics_quality_flags.flag_group, 'general')"),
                DB::raw("COALESCE(analytics_quality_flags.severity, 'info')")
            )
            ->orderByDesc('total_count')
            ->orderBy('flag_group')
            ->limit(12)
            ->get()
            ->map(fn (object $row): array => [
                'group' => (string) $row->flag_group,
                'severity' => (string) $row->severity,
                'total' => (int) $row->total_count,
            ])
            ->values()
            ->all();
    }

    private static function areaComparisonAnalytics(array $context, Builder $base): array
    {
        $scope = (string) ($context['scope'] ?? 'city');

        if ($scope === 'village') {
            return self::areaComparisonByCategory($base);
        }

        $level = $scope === 'district' ? 'village' : 'district';
        $idColumn = $level === 'village' ? 'village_region_id' : 'district_region_id';

        if (
            ! Schema::hasTable('regions')
            || ! Schema::hasColumn('regions', 'id')
            || ! Schema::hasColumn('regions', 'name')
            || ! Schema::hasColumn('umkm_locations', $idColumn)
        ) {
            return [
                'level' => $level,
                'rows' => [],
            ];
        }

        $query = self::cloneQuery($base)
            ->join('regions as analytics_area_regions', 'analytics_area_regions.id', '=', 'umkm_locations.' . $idColumn)
            ->leftJoin('umkm_data_quality_flags as analytics_area_quality_flags', function ($join): void {
                $join->on('analytics_area_quality_flags.umkm_id', '=', 'umkms.id');

                if (Schema::hasColumn('umkm_data_quality_flags', 'status')) {
                    $join->where('analytics_area_quality_flags.status', '=', 'open');
                }
            });

        return [
            'level' => $level,
            'rows' => $query
                ->select(
                    'analytics_area_regions.name',
                    DB::raw('COUNT(DISTINCT umkms.id) as total_umkm'),
                    DB::raw("COUNT(DISTINCT CASE WHEN umkm_locations.coordinate_status = 'terpetakan' AND umkm_locations.latitude IS NOT NULL AND umkm_locations.longitude IS NOT NULL THEN umkms.id END) as mapped_total"),
                    DB::raw('COUNT(DISTINCT analytics_area_quality_flags.umkm_id) as open_quality_notes')
                )
                ->groupBy('analytics_area_regions.id', 'analytics_area_regions.name')
                ->orderByDesc('total_umkm')
                ->orderBy('analytics_area_regions.name')
                ->limit(10)
                ->get()
                ->map(function (object $row): array {
                    $areaTotal = (int) $row->total_umkm;
                    $mapped = (int) $row->mapped_total;

                    return [
                        'name' => (string) ($row->name ?: 'Wilayah'),
                        'total_umkm' => $areaTotal,
                        'mapped_total' => $mapped,
                        'mapped_percentage' => self::percent($mapped, $areaTotal),
                        'open_quality_notes' => (int) $row->open_quality_notes,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private static function areaComparisonByCategory(Builder $base): array
    {
        $categories = self::businessCategoryAnalyticsRows($base, self::countDistinctUmkm($base));

        return [
            'level' => 'category',
            'rows' => collect($categories)
                ->map(fn (array $row): array => [
                    'name' => (string) $row['name'],
                    'total_umkm' => (int) $row['total'],
                    'mapped_total' => 0,
                    'mapped_percentage' => 0.0,
                    'open_quality_notes' => 0,
                ])
                ->values()
                ->all(),
        ];
    }

    private static function decisionRecommendations(array $readiness, array $marketing): array
    {
        $recommendations = [];
        $location = $readiness['location'] ?? [];
        $mappedPercentage = (float) ($location['mapped_percentage'] ?? 0);

        if ($mappedPercentage < 75.0) {
            $recommendations[] = 'Validasi dan pelengkapan titik lokasi perlu menjadi perhatian.';
        }

        foreach (($marketing['methods'] ?? []) as $method) {
            if (($method['name'] ?? '') === 'Belum tersedia' && (int) ($method['total'] ?? 0) > 0) {
                $recommendations[] = 'Metode pemasaran belum lengkap pada sebagian data.';
                break;
            }
        }

        if (($readiness['quality_notes'] ?? []) !== []) {
            $recommendations[] = 'Catatan kualitas data terbuka perlu dipantau sebelum interpretasi lanjutan.';
        }

        if ($recommendations === []) {
            $recommendations[] = 'Data agregat cukup siap untuk ringkasan awal pada wilayah ini.';
        }

        return $recommendations;
    }

    private static function applyClassificationGuards(Builder $query, string $alias): void
    {
        if (Schema::hasColumn('umkm_business_classifications', 'status_data')) {
            $query->whereIn($alias . '.status_data', self::operationalStatuses());
        }

        if (Schema::hasColumn('umkm_business_classifications', 'is_primary')) {
            $query->where($alias . '.is_primary', true);
        }
    }

    private static function applyReferenceActiveGuard(Builder $query, string $table, string $alias): void
    {
        if (Schema::hasColumn($table, 'is_active')) {
            $query->where(function (Builder $query) use ($alias): void {
                $query->where($alias . '.is_active', true)
                    ->orWhereNull($alias . '.is_active');
            });
        }
    }

    private static function detailCard(string $key, array $context, array $cards, int $total, int $mapped, float $mappedPercent, array $dominant, int $activeRegions, float $coverage, int $regionDenominator): array

    {
        $unmapped = max(0, $total - $mapped);
        $card = $cards[$key] ?? null;

        $title = match ($key) {
            'mapped_umkm' => 'Rincian UMKM Terpetakan',
            'dominant_category' => 'Rincian Kategori Dominan',
            'active_regions' => 'Rincian Wilayah Aktif',
            default => 'Rincian UMKM Operasional',
        };

        $summary = match ($key) {
            'mapped_umkm' => [
                ['label' => 'Unit terpetakan', 'value' => self::formatNumber($mapped), 'meta' => 'UMKM dengan titik lokasi'],
                ['label' => 'Belum terpetakan', 'value' => self::formatNumber($unmapped), 'meta' => 'Masih berupa lokasi administratif'],
                ['label' => 'Persentase terpetakan', 'value' => self::formatPercent($mappedPercent), 'meta' => 'Dari UMKM operasional'],
                ['label' => 'Total operasional', 'value' => self::formatNumber($total), 'meta' => 'Basis pembanding'],
            ],
            'dominant_category' => [
                ['label' => 'Kategori dominan', 'value' => (string) $dominant['name'], 'meta' => 'Referensi kategori lokal'],
                ['label' => 'Jumlah kategori dominan', 'value' => self::formatNumber((int) $dominant['count']), 'meta' => 'UMKM pada kategori ini'],
                ['label' => 'Persentase kategori dominan', 'value' => self::formatPercent((float) $dominant['percent']), 'meta' => 'Dari UMKM operasional'],
                ['label' => 'Total operasional', 'value' => self::formatNumber($total), 'meta' => 'Basis pembanding'],
            ],
            'active_regions' => [
                ['label' => 'Wilayah aktif', 'value' => self::formatNumber($activeRegions), 'meta' => 'Kelurahan yang memiliki data UMKM'],
                ['label' => 'Total kelurahan pembanding', 'value' => self::formatNumber($regionDenominator), 'meta' => 'Dasar perhitungan cakupan wilayah'],
                ['label' => 'Cakupan wilayah', 'value' => self::formatPercent($coverage), 'meta' => 'Persentase wilayah yang memiliki data'],
                ['label' => 'UMKM operasional', 'value' => self::formatNumber($total), 'meta' => 'Total agregat pada wilayah aktif'],
            ],
            default => [
                ['label' => 'UMKM operasional', 'value' => self::formatNumber($total), 'meta' => 'Total agregat aman untuk publik'],
                ['label' => 'Unit terpetakan', 'value' => self::formatNumber($mapped), 'meta' => self::formatPercent($mappedPercent) . ' dari total'],
                ['label' => 'Belum terpetakan', 'value' => self::formatNumber($unmapped), 'meta' => 'Perlu pelengkapan titik lokasi'],
                ['label' => 'Kategori dominan', 'value' => (string) $dominant['name'], 'meta' => self::formatPercent((float) $dominant['percent'])],
            ],
        };

        return [
            'key' => $key,
            'title' => $title,
            'subtitle' => $context['label'],
            'card' => $card,
            'summary' => $summary,
            'sections' => [[
                'title' => 'Ringkasan',
                'layout' => 'compact',
                'items' => $summary,
                'empty' => 'Data belum tersedia pada wilayah ini.',
            ]],
            'public_safe_note' => 'Data ditampilkan sebagai agregat aman untuk publik berdasarkan wilayah aktif. Rincian ini tidak memuat identitas pelaku UMKM, kontak, alamat rinci, koordinat presisi, data mentah, atau KBLI aktif.',
        ];
    }

    private static function preview(int $total, int $mapped, float $mappedPercent, int $activeRegions, int $regionDenominator, float $coverage, array $dominant): array
    {
        return [
            'total' => $total,
            'mapped' => $mapped,
            'unmapped' => max(0, $total - $mapped),
            'mapped_percent' => $mappedPercent,
            'active_regions' => $activeRegions,
            'region_denominator' => $regionDenominator,
            'coverage_percent' => $coverage,
            'dominant_category' => (string) ($dominant['name'] ?? 'Belum tersedia'),
            'summary_one' => self::formatNumber($total) . ' UMKM operasional',
            'summary_two' => self::formatPercent($mappedPercent) . ' terpetakan',
            'summary_three' => self::formatNumber($activeRegions) . ' wilayah aktif',
            'note' => 'Data agregat dan peta bersifat aman untuk publik.',
            'empty' => $total < 1,
            'empty_message' => $total < 1 ? 'Data belum tersedia pada wilayah ini.' : null,
        ];
    }

    private static function fields(int $total, float $mappedPercent, float $coverage, array $dominant): array
    {
        return [
            ['label' => 'Operasional', 'percent' => $total > 0 ? 100 : 0, 'note' => 'UMKM pada wilayah aktif'],
            ['label' => 'Terpetakan', 'percent' => $mappedPercent, 'note' => 'UMKM dengan titik lokasi'],
            ['label' => 'Cakupan wilayah', 'percent' => $coverage, 'note' => 'Kelurahan memiliki data'],
            ['label' => 'Kategori dominan', 'percent' => (float) ($dominant['percent'] ?? 0), 'note' => (string) ($dominant['name'] ?? 'Belum tersedia')],
        ];
    }

    private static function chart(int $total, int $mapped, int $activeRegions, float $coverage): array
    {
        return [
            'title' => 'Ringkasan agregat wilayah',
            'labels' => ['Operasional', 'Terpetakan', 'Wilayah aktif'],
            'unit_label' => 'Jumlah',
            'percent_label' => 'Persentase',
            'unit_data' => [$total, $mapped, $activeRegions],
            'percent_data' => [$total > 0 ? 100 : 0, self::percent($mapped, $total), $coverage],
        ];
    }

    private static function cleanDetailCardKey(string $key): ?string
    {
        $key = trim($key);

        return in_array($key, ['total_umkm', 'mapped_umkm', 'dominant_category', 'active_regions'], true) ? $key : null;
    }

    private static function percent(int|float $value, int|float $total): float
    {
        if ($total < 1) {
            return 0.0;
        }

        return round(max(0, min(100, ($value / max(1, $total)) * 100)), 2);
    }

    private static function formatNumber(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    private static function formatPercent(float $value): string
    {
        return number_format(max(0, min(100, $value)), 2, ',', '.') . '%';
    }
}
