Set-Location "C:\laragon\www\umkm-monitoring"

New-Item -ItemType Directory -Force -Path "backups\batch-3a-4-internal-region-api" | Out-Null
New-Item -ItemType Directory -Force -Path "app\Http\Controllers\Api\Internal" | Out-Null

Copy-Item "routes\api-internal.php" "backups\batch-3a-4-internal-region-api\api-internal.php" -Force

if (Test-Path "app\Http\Controllers\Api\Internal\RegionApiController.php") {
    Copy-Item "app\Http\Controllers\Api\Internal\RegionApiController.php" "backups\batch-3a-4-internal-region-api\RegionApiController.php" -Force
}

@'
<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegionApiController extends Controller
{
    protected array $allowedLevels = [
        Region::LEVEL_PROVINCE,
        Region::LEVEL_CITY,
        Region::LEVEL_DISTRICT,
        Region::LEVEL_VILLAGE,
    ];

    public function provinces(Request $request): JsonResponse
    {
        $limit = $this->limit($request);

        $regions = Region::query()
            ->active()
            ->level(Region::LEVEL_PROVINCE)
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return $this->success($regions, [
            'level' => Region::LEVEL_PROVINCE,
            'parent_code' => null,
        ]);
    }

    public function children(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_code' => ['nullable', 'string', 'max:13', 'regex:/^[0-9.]+$/'],
            'level' => ['nullable', Rule::in($this->allowedLevels)],
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $parentCode = $validated['parent_code'] ?? null;
        $requestedLevel = $validated['level'] ?? null;
        $keyword = trim((string) ($validated['q'] ?? ''));
        $limit = $this->limit($request, 200);

        if (!$parentCode && !$requestedLevel) {
            return response()->json([
                'message' => 'Parameter parent_code atau level wajib diisi.',
                'errors' => [
                    'parent_code' => [
                        'Isi parent_code untuk membaca anak wilayah, atau isi level untuk membaca wilayah level tertentu.',
                    ],
                ],
            ], 422);
        }

        $regions = Region::query()
            ->active()
            ->when($parentCode, fn ($query) => $query->where('parent_code', $parentCode))
            ->when($requestedLevel, fn ($query) => $query->where('level', $requestedLevel))
            ->when($keyword !== '', fn ($query) => $query->where('name', 'like', '%' . $keyword . '%'))
            ->orderBy('code')
            ->limit($limit)
            ->get();

        return $this->success($regions, [
            'level' => $requestedLevel,
            'parent_code' => $parentCode,
            'keyword' => $keyword !== '' ? $keyword : null,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'level' => ['nullable', Rule::in($this->allowedLevels)],
            'parent_code' => ['nullable', 'string', 'max:13', 'regex:/^[0-9.]+$/'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $keyword = trim($validated['q']);
        $limit = $this->limit($request, 50);

        $regions = Region::query()
            ->active()
            ->when($validated['level'] ?? null, fn ($query, $level) => $query->where('level', $level))
            ->when($validated['parent_code'] ?? null, fn ($query, $parentCode) => $query->where('parent_code', $parentCode))
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('code', 'like', $keyword . '%');
            })
            ->orderByRaw("
                CASE
                    WHEN code = ? THEN 0
                    WHEN code LIKE ? THEN 1
                    WHEN name LIKE ? THEN 2
                    ELSE 3
                END
            ", [$keyword, $keyword . '%', $keyword . '%'])
            ->orderBy('level')
            ->orderBy('code')
            ->limit($limit)
            ->get();

        return $this->success($regions, [
            'keyword' => $keyword,
            'level' => $validated['level'] ?? null,
            'parent_code' => $validated['parent_code'] ?? null,
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $region = Region::query()
            ->active()
            ->where('code', $code)
            ->first();

        if (!$region) {
            return response()->json([
                'message' => 'Wilayah tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => $this->transform($region, true),
        ]);
    }

    public function breadcrumb(string $code): JsonResponse
    {
        $region = Region::query()
            ->active()
            ->where('code', $code)
            ->first();

        if (!$region) {
            return response()->json([
                'message' => 'Wilayah tidak ditemukan.',
            ], 404);
        }

        $codes = $this->ancestorCodes($code);

        $regions = Region::query()
            ->active()
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        $breadcrumb = collect($codes)
            ->map(fn ($itemCode) => $regions->get($itemCode))
            ->filter()
            ->map(fn (Region $item) => $this->transform($item, false))
            ->values();

        return response()->json([
            'data' => $breadcrumb,
            'meta' => [
                'code' => $code,
                'depth' => $breadcrumb->count(),
            ],
        ]);
    }

    protected function limit(Request $request, int $default = 100): int
    {
        return min(
            max((int) $request->integer('limit', $default), 1),
            500
        );
    }

    protected function success($regions, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $regions
                ->map(fn (Region $region) => $this->transform($region, false))
                ->values(),
            'meta' => array_merge([
                'count' => $regions->count(),
            ], $meta),
        ]);
    }

    protected function transform(Region $region, bool $withRelations = false): array
    {
        $payload = [
            'id' => $region->id,
            'code' => $region->code,
            'name' => $region->name,
            'level' => $region->level,
            'parent_code' => $region->parent_code,
            'province_code' => $region->province_code,
            'city_code' => $region->city_code,
            'district_code' => $region->district_code,
            'village_code' => $region->village_code,
            'source' => $region->source,
            'is_active' => (bool) $region->is_active,
        ];

        if ($withRelations) {
            $payload['parent'] = $region->parent
                ? $this->transform($region->parent, false)
                : null;

            $payload['children_count'] = Region::query()
                ->active()
                ->where('parent_code', $region->code)
                ->count();

            $payload['breadcrumb'] = collect($this->ancestorCodes($region->code))
                ->map(fn ($code) => Region::query()->active()->where('code', $code)->first())
                ->filter()
                ->map(fn (Region $item) => $this->transform($item, false))
                ->values();
        }

        return $payload;
    }

    protected function ancestorCodes(string $code): array
    {
        $parts = explode('.', $code);
        $codes = [];

        for ($i = 1; $i <= count($parts); $i++) {
            $codes[] = implode('.', array_slice($parts, 0, $i));
        }

        return $codes;
    }
}
'@ | Set-Content -Path "app\Http\Controllers\Api\Internal\RegionApiController.php" -Encoding UTF8

@'
<?php

use App\Http\Controllers\Api\Internal\DashboardApiController;
use App\Http\Controllers\Api\Internal\InternalApiController;
use App\Http\Controllers\Api\Internal\RegionApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal')
    ->middleware([
        'auth',
        'throttle:internal-sensitive',
        'validate.internal.origin',
        'validate.internal.referer',
        'validate.fetch.metadata',
        'log.internal.api',
    ])
    ->group(function () {
        Route::get('/dashboard', [InternalApiController::class, 'dashboard'])
            ->middleware('permission:dashboard.view.executive');

        Route::get('/map', [InternalApiController::class, 'map'])
            ->middleware('permission:umkm.read.official');

        Route::get('/table', [InternalApiController::class, 'table'])
            ->middleware('permission:umkm.read.official');

        Route::get('/filter', [InternalApiController::class, 'filter'])
            ->middleware('permission:umkm.read.official');

        Route::post('/upload', [InternalApiController::class, 'upload'])
            ->middleware('permission:umkm.write.official');

        Route::post('/export', [InternalApiController::class, 'export'])
            ->middleware('permission:export.sensitive');

        Route::post('/survey', [InternalApiController::class, 'survey'])
            ->middleware('permission:survey.fill');

        Route::post('/expert-validation', [InternalApiController::class, 'expertValidation'])
            ->middleware('permission:validation.expert.fill');

        Route::get('/audit', [InternalApiController::class, 'audit'])
            ->middleware('permission:audit.read');

        Route::prefix('regions')
            ->middleware('permission:umkm.read.official')
            ->group(function () {
                Route::get('/', [RegionApiController::class, 'children']);
                Route::get('/provinces', [RegionApiController::class, 'provinces']);
                Route::get('/children', [RegionApiController::class, 'children']);
                Route::get('/search', [RegionApiController::class, 'search']);

                Route::get('/{code}/breadcrumb', [RegionApiController::class, 'breadcrumb'])
                    ->where('code', '[0-9.]+');

                Route::get('/{code}', [RegionApiController::class, 'show'])
                    ->where('code', '[0-9.]+');
            });

        Route::get('/dashboard/indicators', [DashboardApiController::class, 'indicators'])
            ->middleware('permission:dashboard.view.executive');

        Route::get('/dashboard/charts', [DashboardApiController::class, 'charts'])
            ->middleware('permission:dashboard.view.executive');

        Route::get('/dashboard/map', [DashboardApiController::class, 'map'])
            ->middleware('permission:dashboard.view.executive');

        Route::get('/dashboard/summary-table', [DashboardApiController::class, 'summaryTable'])
            ->middleware('permission:dashboard.view.executive');
    });
'@ | Set-Content -Path "routes\api-internal.php" -Encoding UTF8

Write-Host "Batch 3A-4 selesai."
Write-Host "File diperbarui:"
Write-Host "- app\Http\Controllers\Api\Internal\RegionApiController.php"
Write-Host "- routes\api-internal.php"
Write-Host "Backup: backups\batch-3a-4-internal-region-api"
