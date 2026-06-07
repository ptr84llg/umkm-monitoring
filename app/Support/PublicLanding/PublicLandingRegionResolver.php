<?php

namespace App\Support\PublicLanding;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicLandingRegionResolver
{
    public static function resolve(array $input): array
    {
        $provinceCode = (string) config('umkm.landing_region.province_code', '16');
        $cityCode = (string) config('umkm.landing_region.city_code', '16.73');
        $districtCode = self::allowedDistrictCode(self::cleanCode($input['district_code'] ?? null), $cityCode);
        $villageCode = self::allowedVillageCode(self::cleanCode($input['village_code'] ?? null), $cityCode, $districtCode);

        $scope = 'city';
        $regionCode = $cityCode;

        if ($districtCode !== null) {
            $scope = 'district';
            $regionCode = $districtCode;
        }

        if ($villageCode !== null) {
            $scope = 'village';
            $regionCode = $villageCode;
            $districtCode = $districtCode ?: self::parentCode($villageCode);
        }

        $provinceName = self::regionName($provinceCode, 'Sumatera Selatan');
        $cityName = self::regionName($cityCode, 'Kota Lubuklinggau');
        $districtName = $districtCode ? self::regionName($districtCode, self::fallbackDistrictName($districtCode)) : null;
        $villageName = $villageCode ? self::regionName($villageCode, self::fallbackVillageName($villageCode)) : null;

        $contextLabel = self::contextLabel($scope, $provinceName, $cityName, $districtName, $villageName);

        return [
            'scope' => $scope,
            'region_code' => $regionCode,
            'province_code' => $provinceCode,
            'province_name' => $provinceName,
            'city_code' => $cityCode,
            'city_name' => $cityName,
            'district_code' => $scope === 'city' ? null : $districtCode,
            'district_name' => $scope === 'city' ? null : $districtName,
            'village_code' => $scope === 'village' ? $villageCode : null,
            'village_name' => $scope === 'village' ? $villageName : null,
            'context_label' => $contextLabel,
        ];
    }

    public static function cleanCode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $upper = strtoupper($text);
        $allTokens = [
            'ALL',
            'SEMUA',
            'NULL',
            'UNKNOWN',
            '__ALL__',
            '__ALL_DISTRICTS__',
            '__ALL_VILLAGES__',
            'ALL_DISTRICTS',
            'ALL_VILLAGES',
            '*',
            '-',
        ];

        if (in_array($upper, $allTokens, true)) {
            return null;
        }

        return $text;
    }

    public static function regionIdByCode(?string $code): ?int
    {
        if ($code === null || ! Schema::hasTable('regions') || ! Schema::hasColumn('regions', 'code')) {
            return null;
        }

        $id = DB::table('regions')->where('code', $code)->value('id');

        return $id === null ? null : (int) $id;
    }

    public static function childRegionIds(string $parentCode): array
    {
        if (! Schema::hasTable('regions')) {
            return [];
        }

        $query = DB::table('regions');

        if (Schema::hasColumn('regions', 'parent_code')) {
            $query->where('parent_code', $parentCode);
        } elseif (Schema::hasColumn('regions', 'district_code')) {
            $query->where('district_code', $parentCode);
        } elseif (Schema::hasColumn('regions', 'code')) {
            $query->where('code', 'like', $parentCode . '.%');
        } else {
            return [];
        }

        if (Schema::hasColumn('regions', 'id')) {
            return $query->pluck('id')->map(fn ($value) => (int) $value)->values()->all();
        }

        return [];
    }

    private static function allowedDistrictCode(?string $code, string $cityCode): ?string
    {
        if ($code === null || ! str_starts_with($code, $cityCode . '.')) {
            return null;
        }

        return self::regionExists($code, ['district', 'kecamatan'], $cityCode) ? $code : null;
    }

    private static function allowedVillageCode(?string $code, string $cityCode, ?string &$districtCode): ?string
    {
        if ($code === null || ! str_starts_with($code, $cityCode . '.')) {
            return null;
        }

        if (! self::regionExists($code, ['village', 'kelurahan', 'desa'], $cityCode)) {
            return null;
        }

        $parentCode = self::parentCode($code);

        if ($parentCode === null || ! str_starts_with($parentCode, $cityCode . '.')) {
            return null;
        }

        if ($districtCode !== null && $districtCode !== $parentCode) {
            return null;
        }

        $districtCode = $districtCode ?: $parentCode;

        return $code;
    }

    private static function regionExists(string $code, array $levels, ?string $cityCode = null): bool
    {
        if (! Schema::hasTable('regions') || ! Schema::hasColumn('regions', 'code')) {
            $codeMatches = preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $code) === 1
                || preg_match('/^\d{2}\.\d{2}\.\d{2}\.\d+$/', $code) === 1;

            return $codeMatches && ($cityCode === null || str_starts_with($code, $cityCode . '.'));
        }

        $query = DB::table('regions')->where('code', $code);

        $levelColumn = self::levelColumn();

        if ($levelColumn !== null) {
            $normalized = array_map(fn ($value) => strtolower((string) $value), $levels);
            $query->whereIn(DB::raw('LOWER(' . $levelColumn . ')'), $normalized);
        }

        if ($cityCode !== null && Schema::hasColumn('regions', 'city_code')) {
            $query->where('city_code', $cityCode);
        }

        return $query->exists();
    }

    private static function regionName(?string $code, string $fallback): string
    {
        if ($code === null || ! Schema::hasTable('regions') || ! Schema::hasColumn('regions', 'code')) {
            return $fallback;
        }

        foreach (['name', 'nama', 'region_name'] as $column) {
            if (Schema::hasColumn('regions', $column)) {
                $value = DB::table('regions')->where('code', $code)->value($column);

                if ($value !== null && trim((string) $value) !== '') {
                    return trim((string) $value);
                }
            }
        }

        return $fallback;
    }

    private static function parentCode(string $code): ?string
    {
        if (Schema::hasTable('regions') && Schema::hasColumn('regions', 'code')) {
            foreach (['parent_code', 'district_code'] as $column) {
                if (Schema::hasColumn('regions', $column)) {
                    $value = DB::table('regions')->where('code', $code)->value($column);

                    if ($value !== null && trim((string) $value) !== '') {
                        return trim((string) $value);
                    }
                }
            }
        }

        $parts = explode('.', $code);

        if (count($parts) >= 3) {
            return implode('.', array_slice($parts, 0, 3));
        }

        return null;
    }

    private static function contextLabel(string $scope, string $provinceName, string $cityName, ?string $districtName, ?string $villageName): string
    {
        if ($scope === 'village' && $villageName !== null && $districtName !== null) {
            return 'Kelurahan ' . $villageName . ', ' . self::cityLabel($cityName, $districtName, $provinceName, true);
        }

        if ($scope === 'district' && $districtName !== null) {
            return self::cityLabel($cityName, $districtName, $provinceName, true);
        }

        return self::normalizeCityName($cityName) . ', ' . $provinceName;
    }

    private static function cityLabel(string $cityName, string $districtName, string $provinceName, bool $withDistrict): string
    {
        if ($withDistrict) {
            return 'Kecamatan ' . $districtName . ', ' . self::normalizeCityName($cityName) . ', ' . $provinceName;
        }

        return self::normalizeCityName($cityName) . ', ' . $provinceName;
    }

    private static function normalizeCityName(string $cityName): string
    {
        $name = trim($cityName);

        if (stripos($name, 'Kota') === 0) {
            return $name;
        }

        return 'Kota ' . $name;
    }

    private static function fallbackDistrictName(string $code): string
    {
        return match ($code) {
            '16.73.01' => 'Lubuk Linggau Timur I',
            '16.73.02' => 'Lubuk Linggau Barat I',
            '16.73.03' => 'Lubuk Linggau Selatan I',
            '16.73.04' => 'Lubuk Linggau Utara I',
            '16.73.05' => 'Lubuk Linggau Timur II',
            '16.73.06' => 'Lubuk Linggau Barat II',
            '16.73.07' => 'Lubuk Linggau Selatan II',
            '16.73.08' => 'Lubuk Linggau Utara II',
            default => $code,
        };
    }

    private static function fallbackVillageName(string $code): string
    {
        return $code;
    }

    private static function levelColumn(): ?string
    {
        foreach (['level', 'type', 'region_level'] as $column) {
            if (Schema::hasColumn('regions', $column)) {
                return $column;
            }
        }

        return null;
    }
}
