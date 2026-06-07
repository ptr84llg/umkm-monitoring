<?php

namespace App\Support\PublicLanding;

use Illuminate\Support\Arr;

final class PublicLandingRegionGeometry
{
    public static function payload(array $input = []): array
    {
        $selection = self::resolveSelection($input);
        $features = self::featuresForSelection($selection);

        return [
            'scope' => $selection['scope'],
            'selection' => $selection,
            'geometry' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
            'summary' => [
                'feature_count' => count($features),
                'visible_level' => $selection['visible_level'],
                'active_label' => $selection['label'],
                'provider' => 'google',
                'contains_umkm_precise_coordinates' => false,
                'contains_raw_geojson_properties' => false,
            ],
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
            return self::districtFeatures($selection);
        }

        $features = self::villageFeatures($selection);

        if ($selection['scope'] === 'district') {
            $districtNumber = $selection['district_number'];

            return array_values(array_filter($features, function (array $feature) use ($districtNumber) {
                return (int) ($feature['properties']['district_number'] ?? 0) === $districtNumber;
            }));
        }

        $districtNumber = $selection['district_number'];
        $villageNumber = $selection['village_number'];

        return array_values(array_filter($features, function (array $feature) use ($districtNumber, $villageNumber) {
            return (int) ($feature['properties']['district_number'] ?? 0) === $districtNumber
                && (int) ($feature['properties']['village_number'] ?? 0) === $villageNumber;
        }));
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
                'region_level' => 'district',
                'parent_code' => $selection['city_code'],
                'district_number' => $districtNumber,
                'active' => $selection['scope'] === 'district' && $selection['district_number'] === $districtNumber,
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

        return [
            'type' => 'Feature',
            'properties' => [
                'region_code' => $regionCode,
                'region_name' => $name,
                'region_level' => 'village',
                'parent_code' => $districtCode,
                'district_number' => $districtNumber,
                'village_number' => $villageNumber,
                'active' => $selection['scope'] === 'village'
                    && $selection['district_number'] === $districtNumber
                    && $selection['village_number'] === $villageNumber,
                'display_order' => $index + 1,
            ],
            'geometry' => self::normalizeGeometry($geometry),
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
}
