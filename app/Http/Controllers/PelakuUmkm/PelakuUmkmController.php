<?php

namespace App\Http\Controllers\PelakuUmkm;

use App\Http\Controllers\Controller;
use App\Models\Umkm\Umkm;
use App\Services\AdminDinas\UmkmOfficialService;
use App\Services\Audit\AuditLogger;
use App\Services\PelakuUmkm\EffectiveUmkmProfileService;
use App\Services\PelakuUmkm\PelakuWorkspaceAccessService;
use Illuminate\Http\Request;

class PelakuUmkmController extends Controller
{
    public function dashboard(
        Request $request,
        PelakuWorkspaceAccessService $accessService
    ) {
        $user = $request->user();
        $owned = $accessService->ownedUmkmQuery($user);

        $umkms = (clone $owned)
            ->orderBy('business_name')
            ->limit(5)
            ->get([
                'id',
                'umkm_code',
                'business_name',
                'status_data',
                'quality_status',
            ]);

        return view('pages.pelaku-umkm.dashboard', [
            'ownedCount' => (clone $owned)->count(),
            'umkms' => $umkms,
        ]);
    }

    public function index(
        Request $request,
        PelakuWorkspaceAccessService $accessService
    ) {
        $umkms = $accessService->ownedUmkmQuery($request->user())
            ->orderBy('business_name')
            ->paginate(20, [
                'id',
                'umkm_code',
                'business_name',
                'status_data',
                'quality_status',
            ])
            ->withQueryString();

        return view('pages.pelaku-umkm.umkm-index', compact('umkms'));
    }

    public function show(
        Request $request,
        Umkm $umkm,
        PelakuWorkspaceAccessService $accessService,
        EffectiveUmkmProfileService $profiles,
        AuditLogger $auditLogger
    ) {
        if (! $accessService->owns($request->user(), $umkm)) {
            abort(404);
        }

        $auditLogger->log(
            'umkm.owner.workspace.view',
            $request,
            'umkms',
            $umkm->id
        );

        $effectiveProfile = $profiles->resolve($umkm);

        return view('pages.pelaku-umkm.umkm-show', compact('umkm', 'effectiveProfile'));
    }
}