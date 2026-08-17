<?php

namespace App\Http\Controllers\PelakuUmkm;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\PelakuUmkm\PelakuBaselineDecisionAnalyticsService;
use Illuminate\Http\Request;

class PelakuAnalyticsController extends Controller
{
    public function index(
        Request $request,
        PelakuBaselineDecisionAnalyticsService $service,
        AuditLogger $auditLogger
    ) {
        $filters = $request->validate([
            'umkm_id' => ['nullable', 'integer', 'min:1'],
            'type_id' => ['nullable', 'integer', 'min:1'],
            'district_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $data = $service->build($request->user(), $filters);

        $auditLogger->log(
            'pelaku_umkm.analytics.baseline.view',
            $request,
            'analytics',
            $data['selected_umkm']['id'] ?? null,
            [],
            [
                'umkm_id' => $data['selected_umkm']['id'] ?? null,
                'type_id' => $data['selected_type']['id'] ?? null,
                'district_id' => $data['selected_district']['id'] ?? null,
                'scope' => 'year_1_baseline_cross_sectional',
            ]
        );

        return view('pages.pelaku-umkm.analytics.index', compact('data'));
    }
}