<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SecurityEventLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LandingRegionController extends Controller
{
    public function context(Request $request): JsonResponse
    {
        $this->guardLandingAjax($request);

        if (! $this->enabled()) {
            abort(404);
        }

        return $this->json([
            'province' => $this->findAllowedProvince(),
            'city' => $this->findAllowedCity(),
            'locked' => [
                'province_locked' => true,
                'city_locked' => true,
                'province_code' => $this->provinceCode(),
                'city_code' => $this->cityCode(),
                'scope' => config('umkm.landing_region.default_scope', 'city'),
            ],
            'options' => [
                'district_all' => $this->virtualAllOption(Region::LEVEL_DISTRICT),
                'village_all' => $this->virtualAllOption(Region::LEVEL_VILLAGE),
            ],
        ], [
            'scope' => 'landing_public_safe_region',
            'message' => 'Wilayah landing dikunci pada Sumatera Selatan dan Kota Lubuklinggau.',
        ]);
    }

    public function children(Request $request): JsonResponse
    {
        $this->guardLandingAjax($request);

        if (! $this->enabled()) {
            abort(404);
        }

        $validated = $request->validate([
            'parent_code' => ['required', 'string', 'max:13', 'regex:/^[0-9.]+$/'],
            'level' => ['required', Rule::in([
                Region::LEVEL_DISTRICT,
                Region::LEVEL_VILLAGE,
            ])],
        ]);

        $parentCode = $validated['parent_code'];
        $level = $validated['level'];

        if (! $this->isAllowedParent($parentCode, $level)) {
            $this->logScopeViolation($request, $parentCode, $level);

            return response()->json([
                'message' => 'Cakupan wilayah tidak diizinkan untuk landing page.',
                'errors' => [
                    'parent_code' => [
                        'Wilayah landing hanya dibatasi pada Sumatera Selatan - Kota Lubuklinggau.',
                    ],
                ],
            ], 403);
        }

        $query = Region::query()
            ->active()
            ->level($level)
            ->where('parent_code', $parentCode)
            ->orderBy('code')
            ->limit($this->maxChildren());

        if ($level === Region::LEVEL_VILLAGE) {
            $query->where('city_code', $this->cityCode());
        }

        $regions = $query
            ->get()
            ->map(fn (Region $region) => $this->transform($region))
            ->values();

        return $this->json([
            'parent_code' => $parentCode,
            'level' => $level,
            'all_option' => $this->virtualAllOption($level),
            'regions' => $regions,
        ], [
            'scope' => 'landing_public_safe_region_children',
            'count' => $regions->count(),
        ]);
    }

    protected function guardLandingAjax(Request $request): void
    {
        $accept = strtolower((string) $request->headers->get('Accept', ''));
        $requestedWith = strtolower((string) $request->headers->get('X-Requested-With', ''));
        $umkmRequest = strtolower((string) $request->headers->get('X-UMKM-Request', ''));

        $validAccept = str_contains($accept, 'application/json') || $request->expectsJson();
        $validAjax = $requestedWith === 'xmlhttprequest';
        $validUmkmRequest = $umkmRequest === 'internal';

        if ($validAccept && $validAjax && $validUmkmRequest) {
            return;
        }

        SecurityEventLog::query()->create([
            'actor_user_id' => $request->user()?->id,
            'event_type' => 'landing_region_invalid_ajax_header',
            'severity' => 'medium',
            'event_detail' => 'Landing region request blocked due to invalid AJAX headers.',
            'ip_address' => $request->ip(),
            'event_time' => now(),
        ]);

        abort(403);
    }

    protected function enabled(): bool
    {
        return (bool) config('umkm.landing_region.enabled', true);
    }

    protected function provinceCode(): string
    {
        return (string) config('umkm.landing_region.province_code', '16');
    }

    protected function provinceName(): string
    {
        return (string) config('umkm.landing_region.province_name', 'Sumatera Selatan');
    }

    protected function cityCode(): string
    {
        return (string) config('umkm.landing_region.city_code', '16.73');
    }

    protected function cityName(): string
    {
        return (string) config('umkm.landing_region.city_name', 'Kota Lubuklinggau');
    }

    protected function maxChildren(): int
    {
        return max(1, min((int) config('umkm.landing_region.max_children', 500), 500));
    }

    protected function findAllowedProvince(): array
    {
        $region = Region::query()
            ->active()
            ->where('code', $this->provinceCode())
            ->where('level', Region::LEVEL_PROVINCE)
            ->first();

        if (! $region) {
            return [
                'code' => $this->provinceCode(),
                'name' => $this->provinceName(),
                'level' => Region::LEVEL_PROVINCE,
                'parent_code' => null,
                'is_virtual' => false,
                'is_config_fallback' => true,
            ];
        }

        return $this->transform($region);
    }

    protected function findAllowedCity(): array
    {
        $region = Region::query()
            ->active()
            ->where('code', $this->cityCode())
            ->where('level', Region::LEVEL_CITY)
            ->where('province_code', $this->provinceCode())
            ->first();

        if (! $region) {
            return [
                'code' => $this->cityCode(),
                'name' => $this->cityName(),
                'level' => Region::LEVEL_CITY,
                'parent_code' => $this->provinceCode(),
                'province_code' => $this->provinceCode(),
                'city_code' => $this->cityCode(),
                'district_code' => null,
                'village_code' => null,
                'is_virtual' => false,
                'is_config_fallback' => true,
            ];
        }

        return $this->transform($region);
    }

    protected function isAllowedParent(string $parentCode, string $level): bool
    {
        if ($level === Region::LEVEL_DISTRICT) {
            return $parentCode === $this->cityCode();
        }

        if ($level === Region::LEVEL_VILLAGE) {
            if (! str_starts_with($parentCode, $this->cityCode() . '.')) {
                return false;
            }

            return Region::query()
                ->active()
                ->where('code', $parentCode)
                ->where('level', Region::LEVEL_DISTRICT)
                ->where('city_code', $this->cityCode())
                ->exists();
        }

        return false;
    }

    protected function virtualAllOption(string $level): array
    {
        if ($level === Region::LEVEL_DISTRICT) {
            return [
                'code' => '__ALL_DISTRICTS__',
                'name' => 'Semua Kecamatan',
                'level' => Region::LEVEL_DISTRICT,
                'parent_code' => $this->cityCode(),
                'is_virtual' => true,
            ];
        }

        return [
            'code' => '__ALL_VILLAGES__',
            'name' => 'Semua Kelurahan',
            'level' => Region::LEVEL_VILLAGE,
            'parent_code' => null,
            'is_virtual' => true,
        ];
    }

    protected function transform(Region $region): array
    {
        return [
            'code' => $region->code,
            'name' => $region->name,
            'level' => $region->level,
            'parent_code' => $region->parent_code,
            'province_code' => $region->province_code,
            'city_code' => $region->city_code,
            'district_code' => $region->district_code,
            'village_code' => $region->village_code,
            'is_virtual' => false,
        ];
    }

    protected function logScopeViolation(Request $request, string $parentCode, string $level): void
    {
        SecurityEventLog::query()->create([
            'actor_user_id' => $request->user()?->id,
            'event_type' => 'landing_region_scope_violation',
            'severity' => 'medium',
            'event_detail' => 'Landing region request attempted to access region outside allowed scope: parent_code=' . $parentCode . ', level=' . $level,
            'ip_address' => $request->ip(),
            'event_time' => now(),
        ]);
    }

    protected function json(array $data, array $meta = []): JsonResponse
    {
        $response = response()->json([
            'data' => $data,
            'meta' => array_merge([
                'public_safe' => true,
                'contains_sensitive_data' => false,
                'allowed_province_code' => $this->provinceCode(),
                'allowed_city_code' => $this->cityCode(),
            ], $meta),
        ]);

        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
