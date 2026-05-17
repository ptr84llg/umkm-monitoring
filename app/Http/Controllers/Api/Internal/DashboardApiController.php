<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Dashboard\DashboardAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    protected array $allowedFilters = [
        'quality_status',
        'region_id',
        'region_code',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
        'kbli_id',
        'kbli_code',
        'legality_status',
        'period_id',
        'business_scale',
        'data_status',
    ];

    public function indicators(
        Request $request,
        DashboardAnalyticsService $svc,
        AuditLogger $audit
    ): JsonResponse {
        $filters = $this->filters($request);

        $audit->log('dashboard.indicators.access', $request, 'dashboard');

        return response()->json([
            'data' => $svc->indicators($filters),
        ]);
    }

    public function charts(
        Request $request,
        DashboardAnalyticsService $svc
    ): JsonResponse {
        $filters = $this->filters($request);

        return response()->json([
            'data' => [
                'kbli_composition' => $svc->kbliComposition($filters),
                'legality_status' => $svc->legalityStatus($filters),
                'performance_trend' => $svc->performanceTrend($filters),
            ],
        ]);
    }

    public function map(
        Request $request,
        DashboardAnalyticsService $svc
    ): JsonResponse {
        $filters = $this->filters($request);

        return response()->json([
            'data' => [
                'aggregate_points' => $svc->mapAggregate($filters),
            ],
        ]);
    }

    public function summaryTable(
        Request $request,
        DashboardAnalyticsService $svc
    ): JsonResponse {
        $filters = $this->filters($request);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(10, (int) $request->query('per_page', 20)));

        $table = $svc->summaryTable($filters, $perPage);

        return response()->json([
            'data' => $table->items(),
            'meta' => [
                'page' => method_exists($table, 'currentPage') ? $table->currentPage() : $page,
                'per_page' => $perPage,
                'total' => method_exists($table, 'total') ? $table->total() : count($table->items()),
            ],
        ]);
    }

    protected function filters(Request $request): array
    {
        return collect($request->only($this->allowedFilters))
            ->filter(fn ($value) => ! is_null($value) && $value !== '')
            ->all();
    }
}
