<?php

namespace App\Http\Controllers\PelakuUmkm;

use App\Http\Controllers\Controller;
use App\Http\Requests\PelakuUmkm\SubmitProfileOverrideRequest;
use App\Models\Reference\MarketingMethodReference;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmUpdateSubmission;
use App\Services\Audit\AuditLogger;
use App\Services\PelakuUmkm\EffectiveUmkmProfileService;
use App\Services\PelakuUmkm\PelakuWorkspaceAccessService;
use App\Services\Proposal\UmkmProposalService;
use Illuminate\Http\Request;

class ProfileOverrideController extends Controller
{
    public function index(Request $request)
    {
        $submissions = UmkmUpdateSubmission::query()
            ->with('umkm:id,umkm_code,business_name')
            ->where('submitted_by', $request->user()->id)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pages.pelaku-umkm.profile-proposals-index', compact('submissions'));
    }

    public function create(
        Request $request,
        Umkm $umkm,
        PelakuWorkspaceAccessService $accessService,
        EffectiveUmkmProfileService $profiles
    ) {
        if (! $accessService->owns($request->user(), $umkm)) {
            abort(404);
        }

        $effectiveProfile = $profiles->resolve($umkm);
        $marketingMethods = MarketingMethodReference::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.pelaku-umkm.profile-proposal-create', compact(
            'umkm',
            'effectiveProfile',
            'marketingMethods'
        ));
    }

    public function store(
        SubmitProfileOverrideRequest $request,
        Umkm $umkm,
        UmkmProposalService $service,
        AuditLogger $auditLogger
    ) {
        $proposal = $service->createProposal([
            'umkm_id' => $umkm->id,
            'business_name' => $request->validated('business_name'),
            'established_date' => $request->validated('established_date'),
            'employee_count' => $request->validated('employee_count'),
            'marketing_method_id' => $request->validated('marketing_method_id'),
            'status_data' => 'diajukan',
        ], $request->user()->id);

        $auditLogger->log(
            'umkm.profile.proposal.create',
            $request,
            'umkm_update_submissions',
            $proposal->id,
            [],
            $proposal->toArray()
        );

        return redirect()
            ->route('pelaku-umkm.profile-proposals.show', $proposal)
            ->with('status', 'Usulan perubahan profil berhasil diajukan tanpa mengubah data sumber.');
    }

    public function show(Request $request, UmkmUpdateSubmission $proposal)
    {
        if ((int) $proposal->submitted_by !== (int) $request->user()->id) {
            abort(404);
        }

        $proposal->load(['umkm:id,umkm_code,business_name', 'reviews']);

        return view('pages.pelaku-umkm.profile-proposal-show', compact('proposal'));
    }
}