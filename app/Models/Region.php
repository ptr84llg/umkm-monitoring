<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    public const LEVEL_PROVINCE = 'province';
    public const LEVEL_CITY = 'city';
    public const LEVEL_DISTRICT = 'district';
    public const LEVEL_VILLAGE = 'village';

    protected $fillable = [
        'code',
        'name',
        'level',
        'parent_code',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
        'source',
        'is_active',
        'imported_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'imported_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeParent(Builder $query, ?string $parentCode): Builder
    {
        return $query->where('parent_code', $parentCode);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_code', 'code');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_code', 'code');
    }

    public static function detectLevel(string $code): string
    {
        $segments = substr_count($code, '.') + 1;

        return match ($segments) {
            1 => self::LEVEL_PROVINCE,
            2 => self::LEVEL_CITY,
            3 => self::LEVEL_DISTRICT,
            4 => self::LEVEL_VILLAGE,
            default => 'unknown',
        };
    }

    public static function detectParentCode(string $code): ?string
    {
        $parts = explode('.', $code);

        if (count($parts) <= 1) {
            return null;
        }

        array_pop($parts);

        return implode('.', $parts);
    }

    public static function buildCodeMap(string $code): array
    {
        $parts = explode('.', $code);
        $level = self::detectLevel($code);

        $provinceCode = $parts[0] ?? null;
        $cityCode = count($parts) >= 2 ? implode('.', array_slice($parts, 0, 2)) : null;
        $districtCode = count($parts) >= 3 ? implode('.', array_slice($parts, 0, 3)) : null;
        $villageCode = $level === self::LEVEL_VILLAGE ? $code : null;

        return [
            'province_code' => $provinceCode,
            'city_code' => $cityCode,
            'district_code' => $districtCode,
            'village_code' => $villageCode,
        ];
    }
}
