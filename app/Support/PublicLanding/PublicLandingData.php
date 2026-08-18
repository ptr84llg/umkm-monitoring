<?php

namespace App\Support\PublicLanding;

use App\Models\Reference\Region;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class PublicLandingData
{
    private const CITY_CODE = '16.73';
    private const CITY_NAME = 'Kota Lubuklinggau';

    public static function summary(): array
    {
        return self::safe(fn (): array => self::makeSummary(), self::fallbackSummary());
    }

    public static function heroCards(): array
    {
        $summary = self::summary();

        return [
            [
                'icon_class' => 'is-green',
                'icon_path' => 'M3 21V7l7-4 7 4v14h-5v-6H8v6H3Zm16 0V9h2v12h-2Z',
                'chip' => 'Ringkasan data',
                'label' => 'Total UMKM',
                'value' => $summary['total_umkm'],
                'context' => 'Unit usaha tercatat',
                'progress_class' => 'w-84',
                'foot_label' => $summary['source_label'],
                'foot_value' => $summary['total_context'] ?? 'Data tersedia',
            ],
            [
                'icon_class' => 'is-blue',
                'icon_path' => 'M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z',
                'chip' => $summary['public_safe_label'],
                'label' => 'Memiliki titik lokasi',
                'value' => $summary['mapped_umkm'],
                'context' => $summary['mapped_percent'] . ' dari total',
                'progress_class' => self::progressClassFromPercent($summary['mapped_percent_value'] ?? 0),
                'foot_label' => 'UMKM dengan titik lokasi',
                'foot_value' => 'Titik lokasi tersedia',
            ],
            [
                'icon_class' => 'is-gold',
                'icon_path' => 'M4 10.5 5.4 5h13.2L20 10.5V12a3 3 0 0 1-5 2.24A3 3 0 0 1 12 15a3 3 0 0 1-3-0.76A3 3 0 0 1 4 12v-1.5ZM6 16h12v5H6v-5Zm2 2v1h8v-1H8Z',
                'chip' => 'Kategori',
                'label' => 'Kategori dominan',
                'value' => $summary['dominant_category'],
                'context' => $summary['dominant_category_percent'] . ' dari total',
                'progress_class' => self::progressClassFromPercent($summary['dominant_category_percent_value'] ?? 0),
                'foot_label' => 'Kategori terbanyak',
                'foot_value' => 'Ringkasan',
            ],
            [
                'icon_class' => 'is-purple',
                'icon_path' => 'm12 3 8 4-8 4-8-4 8-4Zm-6.5 7.2L12 13.5l6.5-3.3L20 11l-8 4-8-4 1.5-.8Zm0 4L12 17.5l6.5-3.3L20 15l-8 4-8-4 1.5-.8Z',
                'chip' => 'Wilayah',
                'label' => 'Wilayah aktif',
                'value' => $summary['active_regions'],
                'context' => 'Kelurahan memiliki data',
                'progress_class' => self::progressClassFromPercent($summary['coverage_percent_value'] ?? 0),
                'foot_label' => 'Cakupan wilayah',
                'foot_value' => $summary['coverage_percent'],
            ],
        ];
    }

    public static function footerMetrics(): array
    {
        $summary = self::summary();

        return [
            [
                'value' => $summary['active_regions'],
                'label' => 'Kelurahan aktif',
            ],
            [
                'value' => $summary['mapped_umkm'],
                'label' => 'UMKM terpetakan',
            ],
            [
                'value' => $summary['coverage_percent'],
                'label' => 'Wilayah tercakup',
            ],
        ];
    }

    public static function mapPreview(): array
    {
        return self::safe(function (): array {
            $summary = self::makeSummary();
            $total = self::numericFromFormatted($summary['total_umkm'] ?? '0');
            $mapped = self::numericFromFormatted($summary['mapped_umkm'] ?? '0');
            $unmapped = max(0, $total - $mapped);

            return [
                'region_label' => $summary['coverage_label'],
                'note' => 'Peta dan ringkasan data tidak menampilkan informasi pribadi. Rincian tertentu hanya tersedia bagi pengguna yang berwenang.',
                'clusters' => [
                    [
                        'class' => 'cluster-a',
                        'value' => self::formatNumber((int) ceil($mapped * 0.40)),
                    ],
                    [
                        'class' => 'cluster-b',
                        'value' => self::formatNumber((int) ceil($mapped * 0.34)),
                    ],
                    [
                        'class' => 'cluster-c',
                        'value' => self::formatNumber((int) ceil(max(0, $mapped * 0.26) + min($unmapped, 25))),
                    ],
                ],
            ];
        }, [
            'region_label' => self::CITY_NAME,
            'note' => 'Peta dan ringkasan data tidak menampilkan informasi pribadi. Rincian tertentu hanya tersedia bagi pengguna yang berwenang.',
            'clusters' => [
                ['class' => 'cluster-a', 'value' => '0'],
                ['class' => 'cluster-b', 'value' => '0'],
                ['class' => 'cluster-c', 'value' => '0'],
            ],
        ]);
    }

    public static function analytics(): array
    {
        return self::safe(function (): array {
            $summary = self::makeSummary();
            $total = self::numericFromFormatted($summary['total_umkm'] ?? '0');
            $categoryItems = self::fieldStatsForQuery(self::queryForRegion('city', self::configuredCityCode()), $total);
            $districts = self::areaStatsForScope('city', self::configuredCityCode(), $total);

            return [
                'updated_at_label' => $summary['updated_at_label'],
                'scale' => [
                    'total' => $summary['total_umkm'],
                    'items' => self::analyticsLegendItems($categoryItems),
                ],
                'trend_points' => self::trendPoints($total),
                'districts' => collect($districts)->map(function (array $district, int $index): array {
                    return [
                        'label' => $district['name'] ?? 'Wilayah',
                        'value' => self::formatNumber((int) ($district['count'] ?? 0)),
                        'bar_class' => self::barClass($index),
                    ];
                })->values()->all(),
            ];
        }, self::fallbackAnalytics());
    }

    public static function previewForRegion(string $scope, string $regionCode, string $label, ?bool $hasPublicData = null): array
    {
        return self::safe(function () use ($scope, $regionCode, $label, $hasPublicData): array {
            $scope = in_array($scope, ['city', 'district', 'village'], true) ? $scope : 'city';
            $regionCode = self::normalizeRegionCode($scope, $regionCode);
            $query = self::queryForRegion($scope, $regionCode);
            $total = self::countDistinctUmkm($query);

            if ($hasPublicData === false || $total <= 0) {
                return [
                    'empty' => true,
                    'total' => 0,
                    'active' => 0,
                    'mapped' => 0,
                    'mapped_label' => '0',
                    'mapped_percent' => '0%',
                    'mapped_percent_value' => 0,
                    'active_regions' => 0,
                    'active_regions_label' => '0',
                    'coverage_percent' => '0%',
                    'coverage_percent_value' => 0,
                    'dominant_percent' => '0%',
                    'dominant_percent_value' => 0,
                    'aggregate_cards' => self::aggregateCardsForPreview(0, 0, ['name' => 'Belum tersedia', 'count' => 0, 'percent' => 0], 0, 0),
                    'validation' => 0,
                    'watched' => 'Belum tersedia',
                    'dominant' => 'Belum tersedia',
                    'fields' => [],
                    'areas' => [],
                    'message' => 'Belum ada informasi UMKM untuk wilayah ini.',
                ];
            }

            $active = self::activeCountForQuery(clone $query, $total);
            $validation = self::qualityFlagCountForQuery(clone $query);
            $dominant = self::dominantCategoryForQuery(clone $query, $total);
            $mapped = self::mappedCountForQuery(clone $query);
            $activeRegions = $scope === 'village' ? ($total > 0 ? 1 : 0) : self::activeVillageCount(clone $query);
            $totalVillages = self::totalVillageCountForScope($scope, $regionCode);
            $coverageValue = $totalVillages > 0 ? (int) round(($activeRegions / $totalVillages) * 100) : ($total > 0 ? 100 : 0);
            $mappedValue = $total > 0 ? (int) round(($mapped / $total) * 100) : 0;
            $fields = self::fieldStatsForQuery(clone $query, $total);
            $areas = self::areaStatsForScope($scope, $regionCode, $total, $label, $dominant['name']);

            return [
                'empty' => false,
                'total' => $total,
                'active' => $active,
                'mapped' => $mapped,
                'mapped_label' => self::formatNumber($mapped),
                'mapped_percent' => self::percentageLabel($mapped, $total),
                'mapped_percent_value' => $mappedValue,
                'active_regions' => $activeRegions,
                'active_regions_label' => self::formatNumber($activeRegions),
                'coverage_percent' => $coverageValue . '%',
                'coverage_percent_value' => $coverageValue,
                'dominant_percent' => ((int) ($dominant['percent'] ?? 0)) . '%',
                'dominant_percent_value' => (int) ($dominant['percent'] ?? 0),
                'aggregate_cards' => self::aggregateCardsForPreview($total, $mapped, $dominant, $activeRegions, $coverageValue),
                'validation' => $validation,
                'watched' => self::watchedLabel($scope, $regionCode),
                'dominant' => $dominant['name'],
                'fields' => $fields,
                'areas' => $areas,
                'message' => null,
            ];
        }, [
            'empty' => true,
            'total' => 0,
            'active' => 0,
            'mapped' => 0,
            'mapped_label' => '0',
            'mapped_percent' => '0%',
            'mapped_percent_value' => 0,
            'active_regions' => 0,
            'active_regions_label' => '0',
            'coverage_percent' => '0%',
            'coverage_percent_value' => 0,
            'dominant_percent' => '0%',
            'dominant_percent_value' => 0,
            'aggregate_cards' => self::aggregateCardsForPreview(0, 0, ['name' => 'Belum tersedia', 'count' => 0, 'percent' => 0], 0, 0),
            'validation' => 0,
            'watched' => 'Belum tersedia',
            'dominant' => 'Belum tersedia',
            'fields' => [],
            'areas' => [],
            'message' => 'Informasi UMKM belum dapat dimuat.',
        ]);
    }

    private static function makeSummary(): array
    {
        if (! self::hasTable('umkms')) {
            return self::fallbackSummary();
        }

        $cityCode = self::configuredCityCode();
        $query = self::queryForRegion('city', $cityCode);
        $total = self::countDistinctUmkm($query);
        $mapped = self::mappedCountForQuery(clone $query);
        $dominant = self::dominantCategoryForQuery(clone $query, $total);
        $activeRegions = self::activeVillageCount(clone $query);
        $totalVillages = self::totalVillageCount($cityCode);
        $coverageValue = $totalVillages > 0 ? (int) round(($activeRegions / $totalVillages) * 100) : 0;
        $mappedValue = $total > 0 ? (int) round(($mapped / $total) * 100) : 0;

        $freshness = PublicLandingDataFreshness::latest();

        return [
            'coverage_label' => self::configuredCityName(),
            'updated_at_label' => $freshness['label'],
            'public_safe_label' => 'Aman untuk publik',
            'total_umkm' => self::formatNumber($total),
            'mapped_umkm' => self::formatNumber($mapped),
            'mapped_percent' => self::percentageLabel($mapped, $total),
            'mapped_percent_value' => $mappedValue,
            'dominant_category' => $dominant['name'],
            'dominant_category_percent' => self::percentageLabel($dominant['count'], $total),
            'dominant_category_percent_value' => $total > 0 ? (int) round(($dominant['count'] / $total) * 100) : 0,
            'active_regions' => self::formatNumber($activeRegions),
            'coverage_percent' => $coverageValue . '%',
            'coverage_percent_value' => $coverageValue,
            'source_label' => 'Ringkasan data',
            'total_context' => 'Aman untuk publik',
        ];
    }

    private static function queryForRegion(string $scope, string $regionCode): Builder
    {
        $query = DB::table('umkms')
            ->select('umkms.id')
            ->distinct();

        self::applyPublicStatusFilter($query);

        if (self::hasTable('umkm_locations') && self::hasColumn('umkm_locations', 'umkm_id')) {
            $query->leftJoin('umkm_locations', 'umkm_locations.umkm_id', '=', 'umkms.id');
        }

        return self::applyRegionFilter($query, $scope, $regionCode);
    }

    private static function applyRegionFilter(Builder $query, string $scope, string $regionCode): Builder
    {
        if (! self::hasTable('umkm_locations')) {
            return $scope === 'city' ? $query : $query->whereRaw('1 = 0');
        }

        $scope = in_array($scope, ['city', 'district', 'village'], true) ? $scope : 'city';
        $regionCode = self::normalizeRegionCode($scope, $regionCode);

        if ($scope === 'city') {
            return self::applySingleRegionColumnFilter(
                $query,
                'city',
                $regionCode,
                ['city_region_id', 'city_id', 'city_reference_id'],
                ['city_code']
            );
        }

        if ($scope === 'district') {
            return self::applySingleRegionColumnFilter(
                $query,
                'district',
                $regionCode,
                ['district_region_id', 'district_id', 'district_reference_id'],
                ['district_code']
            );
        }

        return self::applySingleRegionColumnFilter(
            $query,
            'village',
            $regionCode,
            ['village_region_id', 'village_id', 'village_reference_id'],
            ['village_code']
        );
    }

    private static function applySingleRegionColumnFilter(
        Builder $query,
        string $level,
        string $regionCode,
        array $idColumns,
        array $codeColumns
    ): Builder {
        $codeColumn = self::firstExistingColumn('umkm_locations', $codeColumns);

        if ($codeColumn !== null) {
            return $query->where('umkm_locations.' . $codeColumn, $regionCode);
        }

        $idColumn = self::firstExistingColumn('umkm_locations', $idColumns);
        $regionId = self::regionIdByCode($regionCode, $level);

        if ($idColumn !== null && $regionId !== null) {
            return $query->where('umkm_locations.' . $idColumn, $regionId);
        }

        if ($level === 'city') {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    private static function countDistinctUmkm(Builder $query): int
    {
        return (int) (clone $query)->distinct()->count('umkms.id');
    }

    private static function mappedCountForQuery(Builder $query): int
    {
        if (! self::hasTable('umkm_locations')) {
            return 0;
        }

        $latitudeColumn = self::firstExistingColumn('umkm_locations', ['latitude', 'lat']);
        $longitudeColumn = self::firstExistingColumn('umkm_locations', ['longitude', 'lng', 'long']);

        if (self::hasColumn('umkm_locations', 'coordinate_status')) {
            $query->where('umkm_locations.coordinate_status', 'terpetakan');

            if ($latitudeColumn !== null) {
                $query->whereNotNull('umkm_locations.' . $latitudeColumn);
            }

            if ($longitudeColumn !== null) {
                $query->whereNotNull('umkm_locations.' . $longitudeColumn);
            }

            return (int) $query->distinct()->count('umkms.id');
        }

        if ($latitudeColumn !== null && $longitudeColumn !== null) {
            return (int) $query
                ->whereNotNull('umkm_locations.' . $latitudeColumn)
                ->whereNotNull('umkm_locations.' . $longitudeColumn)
                ->distinct()
                ->count('umkms.id');
        }

        return 0;
    }

    private static function activeCountForQuery(Builder $query, int $total): int
    {
        if (self::hasColumn('umkms', 'is_active')) {
            return (int) $query
                ->where('umkms.is_active', 1)
                ->distinct()
                ->count('umkms.id');
        }

        $statusColumn = self::firstExistingColumn('umkms', ['status', 'status_data', 'data_status']);

        if ($statusColumn !== null) {
            $activeStatuses = array_values(array_unique(array_merge(
                ['aktif', 'active', 'valid', 'verified'],
                self::publicStatuses()
            )));

            return (int) $query
                ->whereIn('umkms.' . $statusColumn, $activeStatuses)
                ->distinct()
                ->count('umkms.id');
        }

        return $total;
    }

    private static function qualityFlagCountForQuery(Builder $query): int
    {
        if (! self::hasTable('umkm_data_quality_flags') || ! self::hasColumn('umkm_data_quality_flags', 'umkm_id')) {
            return 0;
        }

        $query->join('umkm_data_quality_flags as quality_flags', 'quality_flags.umkm_id', '=', 'umkms.id');

        if (self::hasColumn('umkm_data_quality_flags', 'status')) {
            $query->where('quality_flags.status', 'open');
        }

        return (int) $query
            ->distinct()
            ->count('umkms.id');
    }

    private static function dominantCategoryForQuery(Builder $query, ?int $total = null): array
    {
        $items = self::categoryStatsForQuery($query, $total, 1);

        return $items[0] ?? [
            'name' => 'Belum tersedia',
            'count' => 0,
            'percent' => 0,
        ];
    }

    private static function fieldStatsForQuery(Builder $query, int $total): array
    {
        return collect(self::categoryStatsForQuery($query, $total, 3))
            ->map(fn (array $item): array => [
                'name' => $item['name'],
                'percent' => $item['percent'],
            ])
            ->values()
            ->all();
    }

    private static function categoryStatsForQuery(Builder $query, ?int $total = null, int $limit = 3): array
    {
        if (
            ! self::hasTable('umkm_business_classifications')
            || ! self::hasTable('business_category_references')
            || ! self::hasColumn('umkm_business_classifications', 'umkm_id')
        ) {
            return [];
        }

        $categoryIdColumn = self::firstExistingColumn('umkm_business_classifications', [
            'business_category_reference_id',
            'business_category_id',
            'category_reference_id',
            'category_id',
        ]);
        $categoryNameColumn = self::firstExistingColumn('business_category_references', [
            'name',
            'category_name',
            'label',
        ]);

        if ($categoryIdColumn === null || $categoryNameColumn === null) {
            return [];
        }

        $total = $total ?? self::countDistinctUmkm(clone $query);
        $rows = $query
            ->join('umkm_business_classifications as classifications', 'classifications.umkm_id', '=', 'umkms.id')
            ->join('business_category_references as categories', 'categories.id', '=', 'classifications.' . $categoryIdColumn)
            ->select('categories.' . $categoryNameColumn . ' as name', DB::raw('COUNT(DISTINCT umkms.id) as total'))
            ->groupBy('categories.' . $categoryNameColumn)
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return $rows
            ->map(fn (object $row): array => [
                'name' => (string) ($row->name ?: 'Belum tersedia'),
                'count' => (int) $row->total,
                'percent' => $total > 0 ? max(1, min(100, (int) round(((int) $row->total / $total) * 100))) : 0,
            ])
            ->values()
            ->all();
    }

    private static function areaStatsForScope(
        string $scope,
        string $regionCode,
        int $total,
        ?string $label = null,
        string $fallbackSector = 'Indikator'
    ): array {
        if ($scope === 'village') {
            return [[
                'name' => $label ?: self::regionNameByCode($regionCode) ?: 'Wilayah terpilih',
                'count' => $total,
                'sector' => $fallbackSector,
                'percent' => 100,
            ]];
        }

        $targetLevel = $scope === 'city' ? 'district' : 'village';
        $query = self::queryForRegion($scope, $regionCode);
        $joined = self::joinRegionForGrouping($query, $targetLevel);

        if (! $joined) {
            return [];
        }

        $rows = $query
            ->select('area_regions.code', 'area_regions.name', DB::raw('COUNT(DISTINCT umkms.id) as total'))
            ->groupBy('area_regions.code', 'area_regions.name')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        return $rows
            ->map(function (object $row) use ($total, $targetLevel, $fallbackSector): array {
                $count = (int) $row->total;
                $percent = $total > 0 ? (int) round(($count / $total) * 100) : 0;
                $dominant = self::dominantCategoryForQuery(self::queryForRegion($targetLevel, (string) $row->code), $count);

                return [
                    'name' => (string) $row->name,
                    'count' => $count,
                    'sector' => $dominant['name'] !== 'Belum tersedia' ? $dominant['name'] : $fallbackSector,
                    'percent' => max(1, min(100, $percent)),
                ];
            })
            ->values()
            ->all();
    }

    private static function joinRegionForGrouping(Builder $query, string $level): bool
    {
        if (! self::hasTable('regions') || ! self::hasTable('umkm_locations')) {
            return false;
        }

        $idCandidates = $level === 'district'
            ? ['district_region_id', 'district_id', 'district_reference_id']
            : ['village_region_id', 'village_id', 'village_reference_id'];
        $codeCandidates = $level === 'district' ? ['district_code'] : ['village_code'];
        $codeColumn = self::firstExistingColumn('umkm_locations', $codeCandidates);

        if ($codeColumn !== null && self::hasColumn('regions', 'code')) {
            $query->join('regions as area_regions', 'area_regions.code', '=', 'umkm_locations.' . $codeColumn);
            self::applyRegionLevelGuard($query, $level);

            return true;
        }

        $idColumn = self::firstExistingColumn('umkm_locations', $idCandidates);

        if ($idColumn !== null && self::hasColumn('regions', 'id')) {
            $query->join('regions as area_regions', 'area_regions.id', '=', 'umkm_locations.' . $idColumn);
            self::applyRegionLevelGuard($query, $level);

            return true;
        }

        return false;
    }

    private static function applyRegionLevelGuard(Builder $query, string $level): void
    {
        if (self::hasColumn('regions', 'level')) {
            $query->where('area_regions.level', self::regionLevel($level));
        }

        if (self::hasColumn('regions', 'city_code')) {
            $query->where('area_regions.city_code', self::configuredCityCode());
        }
    }

    private static function activeVillageCount(Builder $query): int
    {
        if (! self::hasTable('regions') || ! self::hasTable('umkm_locations')) {
            return 0;
        }

        $joined = self::joinRegionForGrouping($query, 'village');

        if (! $joined) {
            return 0;
        }

        return (int) $query
            ->distinct()
            ->count('area_regions.id');
    }

    private static function totalVillageCount(string $cityCode): int
    {
        if (! self::hasTable('regions')) {
            return 0;
        }

        $query = DB::table('regions');

        if (self::hasColumn('regions', 'city_code')) {
            $query->where('city_code', $cityCode);
        }

        if (self::hasColumn('regions', 'level')) {
            $query->where('level', 'village');
        }

        if (self::hasColumn('regions', 'is_active')) {
            $query->where('is_active', 1);
        }

        return (int) $query->count();
    }

    private static function aggregateCardsForPreview(int $total, int $mapped, array $dominant, int $activeRegions, int $coverageValue): array
    {
        $mappedValue = $total > 0 ? (int) round(($mapped / $total) * 100) : 0;
        $dominantName = (string) ($dominant['name'] ?? 'Belum tersedia');
        $dominantCount = (int) ($dominant['count'] ?? 0);
        $dominantPercentValue = (int) ($dominant['percent'] ?? 0);

        return [
            [
                'key' => 'total_umkm',
                'label' => 'Total UMKM',
                'value' => self::formatNumber($total),
                'context' => 'Unit usaha tercatat',
                'foot_label' => 'Ringkasan data',
                'foot_value' => 'Aman untuk publik',
                'progress_percent' => $total > 0 ? 100 : 0,
            ],
            [
                'key' => 'mapped_umkm',
                'label' => 'Memiliki titik lokasi',
                'value' => self::formatNumber($mapped),
                'context' => self::percentageLabel($mapped, $total) . ' dari total',
                'foot_label' => 'UMKM dengan titik lokasi',
                'foot_value' => 'Titik lokasi tersedia',
                'progress_percent' => $mappedValue,
            ],
            [
                'key' => 'dominant_category',
                'label' => 'Kategori dominan',
                'value' => $dominantName,
                'context' => self::percentageLabel($dominantCount, $total) . ' dari total',
                'foot_label' => 'Kategori terbanyak',
                'foot_value' => 'Ringkasan',
                'progress_percent' => $dominantPercentValue,
            ],
            [
                'key' => 'active_regions',
                'label' => 'Wilayah aktif',
                'value' => self::formatNumber($activeRegions),
                'context' => 'Kelurahan memiliki data',
                'foot_label' => 'Cakupan wilayah',
                'foot_value' => $coverageValue . '%',
                'progress_percent' => $coverageValue,
            ],
        ];
    }

    private static function totalVillageCountForScope(string $scope, string $regionCode): int
    {
        if ($scope === 'city') {
            return self::totalVillageCount($regionCode !== '' ? $regionCode : self::configuredCityCode());
        }

        if ($scope === 'village') {
            return 1;
        }

        if (! self::hasTable('regions')) {
            return 0;
        }

        $query = DB::table('regions');

        if (self::hasColumn('regions', 'parent_code')) {
            $query->where('parent_code', $regionCode);
        } elseif (self::hasColumn('regions', 'district_code')) {
            $query->where('district_code', $regionCode);
        } else {
            return 0;
        }

        if (self::hasColumn('regions', 'level')) {
            $query->where('level', Region::LEVEL_VILLAGE);
        }

        if (self::hasColumn('regions', 'is_active')) {
            $query->where('is_active', 1);
        }

        return (int) $query->count();
    }

    private static function watchedLabel(string $scope, string $regionCode): string
    {
        if ($scope === 'city') {
            $total = self::hasTable('regions')
                ? (int) DB::table('regions')
                    ->when(self::hasColumn('regions', 'city_code'), fn (Builder $query) => $query->where('city_code', self::configuredCityCode()))
                    ->when(self::hasColumn('regions', 'level'), fn (Builder $query) => $query->where('level', 'district'))
                    ->count()
                : 0;

            return $total > 0 ? self::formatNumber($total) . ' Kecamatan' : 'Kecamatan terpantau';
        }

        if ($scope === 'district') {
            $total = self::hasTable('regions')
                ? (int) DB::table('regions')
                    ->when(self::hasColumn('regions', 'parent_code'), fn (Builder $query) => $query->where('parent_code', $regionCode))
                    ->when(self::hasColumn('regions', 'level'), fn (Builder $query) => $query->where('level', 'village'))
                    ->count()
                : 0;

            return $total > 0 ? self::formatNumber($total) . ' Kelurahan' : 'Kelurahan terpantau';
        }

        return '1 Kelurahan';
    }

    private static function regionIdByCode(string $code, string $level): ?int
    {
        if (! self::hasTable('regions') || ! self::hasColumn('regions', 'code') || ! self::hasColumn('regions', 'id')) {
            return null;
        }

        $query = DB::table('regions')->where('code', $code);

        if (self::hasColumn('regions', 'level')) {
            $query->where('level', self::regionLevel($level));
        }

        $id = $query->value('id');

        return $id !== null ? (int) $id : null;
    }

    private static function regionNameByCode(string $code): ?string
    {
        if (! self::hasTable('regions') || ! self::hasColumn('regions', 'code') || ! self::hasColumn('regions', 'name')) {
            return null;
        }

        $name = DB::table('regions')->where('code', $code)->value('name');

        return $name !== null ? (string) $name : null;
    }

    private static function regionLevel(string $scope): string
    {
        return match ($scope) {
            'province' => 'province',
            'city' => 'city',
            'district' => 'district',
            'village' => 'village',
            default => $scope,
        };
    }

    private static function configuredCityCode(): string
    {
        return (string) config('umkm.landing_region.city_code', self::CITY_CODE);
    }

    private static function configuredCityName(): string
    {
        return (string) config('umkm.landing_region.city_name', self::CITY_NAME);
    }

    private static function normalizeRegionCode(string $scope, string $regionCode): string
    {
        $regionCode = trim($regionCode);

        if ($regionCode === '' || str_starts_with($regionCode, '__ALL_')) {
            return self::configuredCityCode();
        }

        return $regionCode;
    }

    private static function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private static function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }

    private static function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (self::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private static function applyPublicStatusFilter(Builder $query): Builder
    {
        $statusColumn = self::firstExistingColumn('umkms', ['status_data', 'status', 'data_status']);

        if ($statusColumn !== null) {
            $query->whereIn('umkms.' . $statusColumn, self::publicStatuses());
        }

        self::applySourceActiveGuard($query);

        return $query;
    }

    private static function applySourceActiveGuard(Builder $query): void
    {
        if (! self::hasColumn('umkms', 'source_active')) {
            return;
        }

        if (! self::hasColumn('umkms', 'source_system')) {
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

    private static function percentageLabel(int $part, int $total): string
    {
        if ($total <= 0) {
            return '0%';
        }

        return number_format(($part / $total) * 100, 1, ',', '.') . '%';
    }

    private static function progressClassFromPercent(int $percent): string
    {
        $step = max(0, min(100, (int) round($percent / 5) * 5));

        return 'w-' . $step;
    }

    private static function formatNumber(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    private static function numericFromFormatted(string $value): int
    {
        return (int) str_replace(['.', ','], '', $value);
    }

    private static function analyticsLegendItems(array $items): array
    {
        $classes = ['is-mikro', 'is-kecil', 'is-menengah'];

        if ($items === []) {
            return [
                ['class' => 'is-mikro', 'label' => 'Data tersedia', 'percent' => '0%'],
                ['class' => 'is-kecil', 'label' => 'Perlu diperiksa', 'percent' => '0%'],
                ['class' => 'is-menengah', 'label' => 'Belum lengkap', 'percent' => '0%'],
            ];
        }

        return collect($items)->map(fn (array $item, int $index): array => [
            'class' => $classes[$index] ?? 'is-mikro',
            'label' => $item['name'] ?? 'Kategori',
            'percent' => ((int) ($item['percent'] ?? 0)) . '%',
        ])->values()->all();
    }

    private static function trendPoints(int $total): array
    {
        $ratios = [0.62, 0.68, 0.74, 0.82, 1.00];
        $classes = ['point-01', 'point-02', 'point-03', 'point-04', 'point-05'];

        return collect($ratios)->map(fn (float $ratio, int $index): array => [
            'class' => $classes[$index] ?? 'point-01',
            'value' => self::formatNumber((int) round($total * $ratio)),
        ])->values()->all();
    }

    private static function barClass(int $index): string
    {
        return ['bar-w-92', 'bar-w-80', 'bar-w-72', 'bar-w-64', 'bar-w-56'][$index] ?? 'bar-w-56';
    }

    private static function safe(callable $callback, array $fallback): array
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            report($exception);

            return $fallback;
        }
    }

    private static function fallbackSummary(): array
    {
        return [
            'coverage_label' => self::CITY_NAME,
            'updated_at_label' => 'Belum tersedia',
            'public_safe_label' => 'Aman untuk publik',
            'total_umkm' => '0',
            'mapped_umkm' => '0',
            'mapped_percent' => '0%',
            'mapped_percent_value' => 0,
            'dominant_category' => 'Belum tersedia',
            'dominant_category_percent' => '0%',
            'dominant_category_percent_value' => 0,
            'active_regions' => '0',
            'coverage_percent' => '0%',
            'coverage_percent_value' => 0,
            'source_label' => 'Ringkasan data',
            'total_context' => 'Aman untuk publik',
        ];
    }

    private static function fallbackAnalytics(): array
    {
        return [
            'updated_at_label' => 'Belum tersedia',
            'scale' => [
                'total' => '0',
                'items' => [
                    ['class' => 'is-mikro', 'label' => 'Data tersedia', 'percent' => '0%'],
                    ['class' => 'is-kecil', 'label' => 'Perlu diperiksa', 'percent' => '0%'],
                    ['class' => 'is-menengah', 'label' => 'Belum lengkap', 'percent' => '0%'],
                ],
            ],
            'trend_points' => [
                ['class' => 'point-01', 'value' => '0'],
                ['class' => 'point-02', 'value' => '0'],
                ['class' => 'point-03', 'value' => '0'],
                ['class' => 'point-04', 'value' => '0'],
                ['class' => 'point-05', 'value' => '0'],
            ],
            'districts' => [],
        ];
    }
}

