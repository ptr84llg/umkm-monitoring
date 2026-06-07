<?php

namespace App\Http\Controllers\AdminDinas;

use App\Actions\AdminDinas\MaskSensitiveUmkmFields;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminDinas\StoreUmkmRequest;
use App\Http\Requests\AdminDinas\UpdateUmkmRequest;
use App\Models\Reference\BusinessCategoryReference;
use App\Models\Reference\BusinessTypeReference;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmBusinessClassification;
use App\Models\Umkm\UmkmLegality;
use App\Models\Umkm\UmkmOwner;
use App\Models\Umkm\UmkmPerformanceRecord;
use App\Models\Umkm\UmkmProduct;
use App\Services\AdminDinas\UmkmOfficialService;
use App\Services\Audit\AuditLogger;

class AdminDinasController extends Controller
{
    public function dashboard()
    {
        return view('pages.admin-dinas.dashboard', [
            'data' => [
                'official_umkm' => Umkm::query()
                    ->whereIn('status_data', $this->operationalStatuses())
                    ->count(),
                'need_fix' => Umkm::query()->where('status_data', 'perlu_perbaikan')->count(),
                'pending' => Umkm::query()->where('status_data', 'diajukan')->count(),
            ],
        ]);
    }

    public function index()
    {
        return view('pages.admin-dinas.umkm-index', [
            'umkms' => Umkm::query()->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return view('pages.admin-dinas.umkm-create');
    }

    public function store(StoreUmkmRequest $request, UmkmOfficialService $service, AuditLogger $auditLogger)
    {
        $umkm = $service->createOfficial($request->validated(), $request->user()->id);
        $auditLogger->log('umkm.official.create', $request, 'umkms', $umkm->id, [], $umkm->toArray());

        return redirect()->route('admin-dinas.umkm.show', $umkm)->with('status', 'Data UMKM operasional ditambahkan.');
    }

    public function show(Umkm $umkm, MaskSensitiveUmkmFields $mask)
    {
        $profile = [
            'umkm' => $umkm,
            'owners' => UmkmOwner::query()->where('umkm_id', $umkm->id)->get(),
            'classifications' => UmkmBusinessClassification::query()
                ->with(['category', 'businessType'])
                ->where('umkm_id', $umkm->id)
                ->get(),
            'legalities' => UmkmLegality::query()->where('umkm_id', $umkm->id)->get(),
            'products' => UmkmProduct::query()->where('umkm_id', $umkm->id)->get(),
            'performance' => UmkmPerformanceRecord::query()->where('umkm_id', $umkm->id)->get(),
            'quality_status' => $umkm->quality_status,
            'history' => [],
        ];

        $masked = $mask->execute([
            'owner_phone' => optional($profile['owners']->first())->phone,
            'owner_email' => optional($profile['owners']->first())->email,
            'nib_number' => optional($profile['legalities']->first())->nib_number,
            'oss_risk_level' => optional($profile['legalities']->first())->oss_risk_level,
            'monthly_revenue' => optional($profile['performance']->first())->monthly_revenue,
            'latitude' => optional($umkm->locations()->first())->latitude,
            'longitude' => optional($umkm->locations()->first())->longitude,
            'coaching_notes' => $umkm->notes,
        ], auth()->user());

        return view('pages.admin-dinas.umkm-show', compact('profile', 'masked'));
    }

    public function edit(Umkm $umkm)
    {
        return view('pages.admin-dinas.umkm-edit', compact('umkm'));
    }

    public function update(UpdateUmkmRequest $request, Umkm $umkm, UmkmOfficialService $service, AuditLogger $auditLogger)
    {
        $before = $umkm->toArray();
        $updated = $service->updateOfficial($umkm, $request->validated(), $request->user()->id);
        $auditLogger->log('umkm.official.update', $request, 'umkms', $umkm->id, $before, $updated->toArray());

        return redirect()->route('admin-dinas.umkm.show', $umkm)->with('status', 'Data UMKM operasional diperbarui.');
    }

    public function references()
    {
        return view('pages.admin-dinas.references', [
            'categories' => BusinessCategoryReference::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'types' => BusinessTypeReference::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(100)
                ->get(['id', 'name', 'slug']),
        ]);
    }

    private function operationalStatuses(): array
    {
        return array_values(array_filter(array_map(
            fn ($status) => trim((string) $status),
            (array) config('umkm.data.operational_statuses', ['resmi', 'terbatas'])
        )));
    }
}
