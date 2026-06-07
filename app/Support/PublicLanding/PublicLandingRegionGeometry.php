<?php

namespace App\Support\PublicLanding;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PublicLandingRegionGeometry
{
    public static function payload(array $input = []): array
    {
        $selection = self::resolveSelection($input);
        $features = self::featuresForSelection($selection);
        $summary = self::summaryFromFeatures($features, $selection);

        return [
            'scope' => $selection['scope'],
            'selection' => $selection,
            'geometry' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
            'summary' => $summary,
        ];
    }

    private static function resolveSelection(array $input): array
    {
        $cityCode = (string) config('umkm.landing_region.city_code', '16.73');
        $cityName = (string) config('umkm.landing_region.city_name', 'Kota Lubuklinggau');

        $scope = (string) ($input['scope'] ?? 'city');
        $districtCode = self::cleanCode((string) ($input['district_code'] ?? ''));
        $villageCode = self::cleanCode((string) ($input['village_code'] ?? ''));

        if ($villageCode !== '') {
            $scope = 'village';
        } elseif ($districtCode !== '') {
            $scope = 'district';
        } elseif (! in_array($scope, ['city', 'district', 'village'], true)) {
            $scope = 'city';
        }

        if ($districtCode !== '' && ! self::belongsToCity($districtCode, $cityCode)) {
            $districtCode = '';
            $villageCode = '';
            $scope = 'city';
        }

        if ($villageCode !== '' && ! self::belongsToCity($villageCode, $cityCode)) {
            $villageCode = '';
            $scope = $districtCode !== '' ? 'district' : 'city';
        }

        $label = $cityName;
        $visibleLevel = 'district';

        if ($scope === 'district' && $districtCode !== '') {
            $visibleLevel = 'village';
            $label = 'Kecamatan ' . self::cleanName((string) ($input['district_name'] ?? $districtCode));
        }

        if ($scope === 'village' && $villageCode !== '') {
            $visibleLevel = 'village';
            $label = 'Kelurahan ' . self::cleanName((string) ($input['village_name'] ?? $villageCode));
        }

        return [
            'scope' => $scope,
            'city_code' => $cityCode,
            'city_name' => $cityName,
            'district_code' => $districtCode,
            'village_code' => $villageCode,
            'district_number' => self::districtNumberFromCode($districtCode),
            'village_number' => self::villageNumberFromCode($villageCode),
            'visible_level' => $visibleLevel,
            'label' => $label,
        ];
    }

    private static function featuresForSelection(array $selection): array
    {
        if ($selection['scope'] === 'city') {
            return self::enrichFeaturesWithUmkmCounts(
                self::districtFeatures($selection),
                'district'
            );
        }

        $features = self::villageFeatures($selection);

        if ($selection['scope'] === 'district') {
            $districtNumber = $selection['district_number'];

            $features = array_values(array_filter($features, function (array $feature) use ($districtNumber) {
                return (int) ($feature['properties']['district_number'] ?? 0) === $districtNumber;
            }));

            return self::enrichFeaturesWithUmkmCounts($features, 'village');
        }

        $districtNumber = $selection['district_number'];
        $villageNumber = $selection['village_number'];

        $features = array_values(array_filter($features, function (array $feature) use ($districtNumber, $villageNumber) {
            return (int) ($feature['properties']['district_number'] ?? 0) === $districtNumber
                && (int) ($feature['properties']['village_number'] ?? 0) === $villageNumber;
        }));

        return self::enrichFeaturesWithUmkmCounts($features, 'village');
    }

    private static function districtFeatures(array $selection): array
    {
        return collect(self::readGeojson(config('umkm.landing_region.geometry.district_geojson_path')))
            ->map(fn (array $feature, int $index) => self::transformDistrictFeature($feature, $selection, $index))
            ->filter()
            ->values()
            ->all();
    }

    private static function villageFeatures(array $selection): array
    {
        return collect(self::readGeojson(config('umkm.landing_region.geometry.village_geojson_path')))
            ->map(fn (array $feature, int $index) => self::transformVillageFeature($feature, $selection, $index))
            ->filter()
            ->values()
            ->all();
    }

    private static function transformDistrictFeature(array $feature, array $selection, int $index): ?array
    {
        $properties = Arr::get($feature, 'properties', []);
        $geometry = Arr::get($feature, 'geometry');

        if (! is_array($geometry)) {
            return null;
        }

        $districtNumber = (int) ltrim((string) ($properties['kd_kecamatan'] ?? '0'), '0');
        $regionCode = self::districtCodeFromNumber($districtNumber);
        $name = self::cleanName((string) ($properties['nm_kecamatan'] ?? 'Kecamatan'));

        return [
            'type' => 'Feature',
            'properties' => [
                'region_code' => $regionCode,
                'region_name' => $name,
                'region_label' => 'Kecamatan ' . $name,
                'region_level' => 'district',
                'parent_code' => $selection['city_code'],
                'district_code' => $regionCode,
                'district_name' => $name,
                'district_number' => $districtNumber,
                'active' => $selection['scope'] === 'district' && $selection['district_number'] === $districtNumber,
                'selectable' => true,
                'display_order' => $index + 1,
            ],
            'geometry' => self::normalizeGeometry($geometry),
        ];
    }

    private static function transformVillageFeature(array $feature, array $selection, int $index): ?array
    {
        $properties = Arr::get($feature, 'properties', []);
        $geometry = Arr::get($feature, 'geometry');

        if (! is_array($geometry)) {
            return null;
        }

        $districtNumber = (int) ltrim((string) ($properties['kd_kecamatan'] ?? '0'), '0');
        $villageNumber = (int) ltrim((string) ($properties['kd_kelurahan'] ?? '0'), '0');
        $districtCode = self::districtCodeFromNumber($districtNumber);
        $regionCode = self::villageCodeFromNumber($districtNumber, $villageNumber);
        $name = self::cleanName((string) ($properties['nm_kelurahan'] ?? 'Kelurahan'));
        $districtName = self::cleanName((string) ($properties['nm_kecamatan'] ?? $districtCode));

        return [
            'type' => 'Feature',
            'properties' => [
                'region_code' => $regionCode,
                'region_name' => $name,
                'region_label' => 'Kelurahan ' . $name,
                'region_level' => 'village',
                'parent_code' => $districtCode,
                'district_code' => $districtCode,
                'district_name' => $districtName,
                'village_code' => $regionCode,
                'village_name' => $name,
                'district_number' => $districtNumber,
                'village_number' => $villageNumber,
                'active' => $selection['scope'] === 'village'
                    && $selection['district_number'] === $districtNumber
                    && $selection['village_number'] === $villageNumber,
                'selectable' => true,
                'display_order' => $index + 1,
            ],
            'geometry' => self::normalizeGeometry($geometry),
        ];
    }

    private static function enrichFeaturesWithUmkmCounts(array $features, string $level): array
    {
        $codes = collect($features)
            ->map(fn (array $feature): string => (string) ($feature['properties']['region_code'] ?? ''))
            ->filter(fn (string $code): bool => $code !== '')
            ->unique()
            ->values()
            ->all();

        $counts = self::umkmCountsByRegionCodes($codes, $level);
        $max = max(0, ...array_values($counts));

        return collect($features)
            ->map(function (array $feature) use ($counts, $max): array {
                $code = (string) ($feature['properties']['region_code'] ?? '');
                $total = (int) ($counts[$code] ?? 0);
                $percent = $max > 0 ? round(($total / $max) * 100, 2) : 0.0;

                $feature['properties']['umkm_total'] = $total;
                $feature['properties']['umkm_total_text'] = self::formatNumber($total);
                $feature['properties']['density_percent'] = $percent;
                $feature['properties']['density_level'] = self::densityLevel($total, $percent);
                $feature['properties']['has_public_umkm_data'] = $total > 0;

                return $feature;
            })
            ->values()
            ->all();
    }

    private static function umkmCountsByRegionCodes(array $codes, string $level): array
    {
        $codes = array_values(array_filter(array_unique($codes), fn (string $code): bool => trim($code) !== ''));

        if ($codes === [] || ! Schema::hasTable('umkms') || ! Schema::hasTable('umkm_locations')) {
            return [];
        }

        $query = self::baseUmkmQuery();

        $codeColumn = self::firstColumn('umkm_locations', [$level . '_code']);

        if ($codeColumn !== null) {
            return $query
                ->whereIn('umkm_locations.' . $codeColumn, $codes)
                ->selectRaw('umkm_locations.' . self::wrap($codeColumn) . ' as region_code, COUNT(DISTINCT umkms.id) as total_count')
                ->groupBy('umkm_locations.' . $codeColumn)
                ->pluck('total_count', 'region_code')
                ->map(fn ($value): int => (int) $value)
                ->all();
        }

        $idColumn = self::firstColumn('umkm_locations', $level === 'district'
            ? ['district_region_id', 'district_id', 'district_reference_id']
            : ['village_region_id', 'village_id', 'village_reference_id']);

        if ($idColumn === null || ! Schema::hasTable('regions') || ! Schema::hasColumn('regions', 'id') || ! Schema::hasColumn('regions', 'code')) {
            return [];
        }

        return $query
            ->join('regions as region_counts', 'region_counts.id', '=', 'umkm_locations.' . $idColumn)
            ->whereIn('region_counts.code', $codes)
            ->selectRaw('region_counts.code as region_code, COUNT(DISTINCT umkms.id) as total_count')
            ->groupBy('region_counts.code')
            ->pluck('total_count', 'region_code')
            ->map(fn ($value): int => (int) $value)
            ->all();
    }

    private static function baseUmkmQuery(): Builder
    {
        $query = DB::table('umkms')
            ->leftJoin('umkm_locations', 'umkm_locations.umkm_id', '=', 'umkms.id');

        return self::applyPublicStatusFilter($query);
    }

    private static function applyPublicStatusFilter(Builder $query): Builder
    {
        if (! Schema::hasColumn('umkms', 'status_data')) {
            return $query;
        }

        $statuses = self::publicStatuses();

        if ($statuses === []) {
            return $query;
        }

        return $query->whereIn('umkms.status_data', $statuses);
    }

    private static function publicStatuses(): array
    {
        $statuses = array_values(array_filter(array_map(
            fn ($status) => trim((string) $status),
            (array) config('umkm.data.public_statuses', ['resmi', 'terbatas'])
        )));

        return $statuses === [] ? ['resmi', 'terbatas'] : $statuses;
    }

    private static function densityLevel(int $total, float $percent): string
    {
        if ($total < 1) {
            return 'empty';
        }

        if ($percent >= 75) {
            return 'high';
        }

        if ($percent >= 40) {
            return 'medium';
        }

        return 'low';
    }

    private static function summaryFromFeatures(array $features, array $selection): array
    {
        $counts = collect($features)
            ->map(fn (array $feature): int => (int) ($feature['properties']['umkm_total'] ?? 0))
            ->values();

        $total = (int) $counts->sum();
        $max = (int) ($counts->max() ?? 0);
        $active = collect($features)->first(fn (array $feature): bool => (bool) ($feature['properties']['active'] ?? false));
        $activeTotal = $active ? (int) ($active['properties']['umkm_total'] ?? 0) : null;

        return [
            'feature_count' => count($features),
            'visible_level' => $selection['visible_level'],
            'active_label' => $selection['label'],
            'provider' => 'google',
            'interactive' => true,
            'contains_umkm_precise_coordinates' => false,
            'contains_raw_geojson_properties' => false,
            'contains_umkm_aggregate_counts' => true,
            'total_umkm_count' => $total,
            'total_umkm_text' => self::formatNumber($total),
            'max_umkm_count' => $max,
            'max_umkm_text' => self::formatNumber($max),
            'active_umkm_count' => $activeTotal,
            'active_umkm_text' => $activeTotal === null ? '-' : self::formatNumber($activeTotal),
        ];
    }

    private static function readGeojson(?string $path): array
    {
        if (! $path || ! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || ($decoded['type'] ?? null) !== 'FeatureCollection') {
            return [];
        }

        return array_values(array_filter($decoded['features'] ?? [], 'is_array'));
    }

    private static function normalizeGeometry(array $geometry): array
    {
        return [
            'type' => (string) ($geometry['type'] ?? 'MultiPolygon'),
            'coordinates' => self::normalizeCoordinates($geometry['coordinates'] ?? []),
        ];
    }

    private static function normalizeCoordinates($value)
    {
        if (! is_array($value)) {
            return $value;
        }

        if (count($value) >= 2 && is_numeric($value[0] ?? null) && is_numeric($value[1] ?? null)) {
            return [(float) $value[0], (float) $value[1]];
        }

        return array_map(fn ($item) => self::normalizeCoordinates($item), $value);
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

    private static function cleanCode(string $code): string
    {
        return preg_replace('/[^0-9.]/', '', trim($code)) ?? '';
    }

    private static function belongsToCity(string $code, string $cityCode): bool
    {
        return $code === $cityCode || str_starts_with($code, $cityCode . '.');
    }

    private static function districtNumberFromCode(string $code): int
    {
        $parts = array_values(array_filter(explode('.', $code), fn ($part) => $part !== ''));

        if (count($parts) < 3) {
            return 0;
        }

        return (int) end($parts);
    }

    private static function villageNumberFromCode(string $code): int
    {
        $parts = array_values(array_filter(explode('.', $code), fn ($part) => $part !== ''));

        if (count($parts) < 4) {
            return 0;
        }

        return (int) end($parts);
    }

    private static function districtCodeFromNumber(int $number): string
    {
        return (string) config('umkm.landing_region.city_code', '16.73') . '.' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    private static function villageCodeFromNumber(int $districtNumber, int $villageNumber): string
    {
        return self::districtCodeFromNumber($districtNumber) . '.' . str_pad((string) $villageNumber, 3, '0', STR_PAD_LEFT);
    }

    private static function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
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
