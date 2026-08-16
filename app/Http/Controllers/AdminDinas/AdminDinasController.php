<?php

namespace App\Http\Controllers\AdminDinas;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminDinas\StoreUmkmRequest;
use App\Http\Requests\AdminDinas\UpdateUmkmRequest;
use App\Models\Reference\BusinessCategoryReference;
use App\Models\Reference\BusinessTypeReference;
use App\Models\Umkm\Umkm;
use App\Services\AdminDinas\AdminDinasDashboardService;
use App\Services\AdminDinas\AdminDinasWorkspaceService;
use App\Services\AdminDinas\UmkmOfficialService;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;

class AdminDinasController extends Controller
{
    public function dashboard(Request $request, AdminDinasDashboardService $service, AuditLogger $audit)
    {
        $filters = $this->analyticsFilters($request);
        $canFinancial = (bool) $request->user()?->hasPermission('umkm.sensitive.financial');
        $data = $service->build($filters, $canFinancial);

        $audit->log('admin_dinas.dashboard.view', $request, 'dashboard', null, [], [
            'filters' => $filters,
            'financial_access' => $canFinancial,
            'scope' => 'internal_read_only',
        ]);

        return view('pages.admin-dinas.dashboard', compact('data'));
    }

    public function index(Request $request, AdminDinasWorkspaceService $service, AuditLogger $audit)
    {
        $filters = $this->workspaceFilters($request);
        $canFinancial = (bool) $request->user()?->hasPermission('umkm.sensitive.financial');
        $data = $service->umkmIndex($filters, $canFinancial);

        $audit->log('admin_dinas.umkm.index.view', $request, 'umkms', null, [], [
            'filters' => array_diff_key($filters, ['search' => true]),
            'search_used' => ! empty($filters['search']),
            'financial_access' => $canFinancial,
            'scope' => 'internal_read_only',
        ]);

        return view('pages.admin-dinas.umkm-index', compact('data'));
    }

    public function show(Request $request, Umkm $umkm, AdminDinasWorkspaceService $service, AuditLogger $audit)
    {
        $data = $service->umkmDetail($umkm, $request->user());

        $audit->log('admin_dinas.umkm.detail.view', $request, 'umkms', $umkm->id, [], [
            'financial_access' => (bool) $request->user()?->hasPermission('umkm.sensitive.financial'),
            'scope' => 'internal_read_only',
        ]);

        return view('pages.admin-dinas.umkm-show', compact('data'));
    }

    public function analytics(Request $request, AdminDinasWorkspaceService $service, AuditLogger $audit)
    {
        $filters = $this->analyticsFilters($request);
        $canFinancial = (bool) $request->user()?->hasPermission('umkm.sensitive.financial');
        $data = $service->analyticsOverview($filters, $canFinancial);

        $audit->log('admin_dinas.analytics.view', $request, 'analytics', null, [], [
            'filters' => $filters,
            'financial_access' => $canFinancial,
            'scope' => 'internal_read_only',
        ]);

        return view('pages.admin-dinas.analytics.index', compact('data'));
    }

    public function financialAnalytics(Request $request, AdminDinasWorkspaceService $service, AuditLogger $audit)
    {
        $filters = $this->analyticsFilters($request);
        $data = $service->financialAnalyticsPage($filters);

        $audit->log('admin_dinas.analytics.financial.view', $request, 'analytics', null, [], [
            'filters' => $filters,
            'scope' => 'internal_sensitive_read_only',
        ]);

        return view('pages.admin-dinas.analytics.financial', compact('data'));
    }

    /*
     * Foundation CRUD tetap dipertahankan di kode, tetapi tidak di-route pada
     * Refine-Evaluate. Batch ini hanya mengaktifkan pembacaan internal.
     */
    public function create()
    {
        return view('pages.admin-dinas.umkm-create');
    }

    public function store(StoreUmkmRequest $request, UmkmOfficialService $service, AuditLogger $audit)
    {
        $umkm = $service->createOfficial($request->validated(), $request->user()->id);
        $audit->log('umkm.official.create', $request, 'umkms', $umkm->id, [], $umkm->toArray());

        return redirect()->route('admin-dinas.umkm.show', $umkm)->with('status', 'Data UMKM operasional ditambahkan.');
    }

    public function edit(Umkm $umkm)
    {
        return view('pages.admin-dinas.umkm-edit', compact('umkm'));
    }

    public function update(UpdateUmkmRequest $request, Umkm $umkm, UmkmOfficialService $service, AuditLogger $audit)
    {
        $before = $umkm->toArray();
        $updated = $service->updateOfficial($umkm, $request->validated(), $request->user()->id);
        $audit->log('umkm.official.update', $request, 'umkms', $umkm->id, $before, $updated->toArray());

        return redirect()->route('admin-dinas.umkm.show', $umkm)->with('status', 'Data UMKM operasional diperbarui.');
    }

    public function references()
    {
        return view('pages.admin-dinas.references', [
            'categories' => BusinessCategoryReference::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'types' => BusinessTypeReference::query()->where('is_active', true)->orderBy('name')->limit(100)->get(['id', 'name', 'slug']),
        ]);
    }

    private function analyticsFilters(Request $request): array
    {
        return $request->validate([
            'district_id' => ['nullable', 'integer', 'min:1'],
            'village_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'type_id' => ['nullable', 'integer', 'min:1'],
            'marketing_method_id' => ['nullable', 'integer', 'min:1'],
            'quality_status' => ['nullable', 'string', 'max:80'],
        ]);
    }

    private function workspaceFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'district_id' => ['nullable', 'integer', 'min:1'],
            'village_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'type_id' => ['nullable', 'integer', 'min:1'],
            'marketing_method_id' => ['nullable', 'integer', 'min:1'],
            'quality_status' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
        ]);
    }
}
