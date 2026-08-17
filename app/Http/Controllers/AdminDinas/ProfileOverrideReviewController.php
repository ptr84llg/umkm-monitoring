<?php

namespace App\Http\Controllers\AdminDinas;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminDinas\ReviewProfileOverrideRequest;
use App\Models\Umkm\UmkmUpdateSubmission;
use App\Services\Audit\AuditLogger;
use App\Services\PelakuUmkm\ApprovedProfileOverrideService;
use App\Services\PelakuUmkm\EffectiveUmkmProfileService;
use App\Services\Proposal\UmkmProposalService;
use Illuminate\Http\Request;

class ProfileOverrideReviewController extends Controller
{
    private const STATUSES = ['diajukan', 'disetujui', 'perlu_perbaikan', 'ditolak'];

    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'diajukan');
        if (! in_array($status, self::STATUSES, true)) {
            $status = 'diajukan';
        }

        $submissions = UmkmUpdateSubmission::query()
            ->where('submission_payload->schema', 'profile_override.v1')
            ->where('status_data', $status)
            ->with([
                'umkm:id,umkm_code,business_name',
                'submittedBy:id,name,email',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('pages.admin-dinas.profile-reviews-index', [
            'submissions' => $submissions,
            'status' => $status,
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(
        UmkmUpdateSubmission $proposal,
        EffectiveUmkmProfileService $profiles,
        ApprovedProfileOverrideService $approvedOverrides
    ) {
        $this->assertProfileOverride($proposal);

        $proposal->load([
            'umkm:id,umkm_code,business_name,established_date,status_data,quality_status',
            'submittedBy:id,name,email',
            'reviews.reviewer:id,name,email',
            'overrideRevision',
        ]);

        $currentProfile = $profiles->resolve($proposal->umkm);
        $conflictingFields = $approvedOverrides->conflictingFields($proposal);

        return view('pages.admin-dinas.profile-review-show', [
            'proposal' => $proposal,
            'currentProfile' => $currentProfile,
            'conflictingFields' => $conflictingFields,
            'labels' => EffectiveUmkmProfileService::EDITABLE_FIELDS,
        ]);
    }

    public function review(
        ReviewProfileOverrideRequest $request,
        UmkmUpdateSubmission $proposal,
        UmkmProposalService $service,
        AuditLogger $auditLogger
    ) {
        $this->assertProfileOverride($proposal);

        $updated = $service->reviewProposal(
            $proposal,
            $request->validated('decision'),
            $request->validated('review_note'),
            (int) $request->user()->id,
            $request,
            $auditLogger
        );

        return redirect()
            ->route('admin-dinas.profile-reviews.show', $updated)
            ->with('status', 'Review perubahan profil berhasil disimpan tanpa mengubah data sumber.');
    }

    private function assertProfileOverride(UmkmUpdateSubmission $proposal): void
    {
        if (data_get($proposal->submission_payload, 'schema') !== 'profile_override.v1') {
            abort(404);
        }
    }
}