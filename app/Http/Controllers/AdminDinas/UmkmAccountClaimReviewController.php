<?php

namespace App\Http\Controllers\AdminDinas;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminDinas\InviteUmkmAccountClaimRequest;
use App\Http\Requests\AdminDinas\ReviewUmkmAccountClaimRequest;
use App\Models\Umkm\UmkmAccountClaim;
use App\Services\PelakuUmkm\AccountClaimActivationService;
use Illuminate\Http\Request;

class UmkmAccountClaimReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', ''));

        $query = UmkmAccountClaim::query()
            ->with([
                'umkm:id,umkm_code,business_name',
                'reviewedBy:id,name,email',
                'activatedUser:id,name,email,is_active',
            ])
            ->latest('submitted_at')
            ->latest('id');

        if (in_array($status, [
            UmkmAccountClaim::STATUS_PENDING_REVIEW,
            UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION,
            UmkmAccountClaim::STATUS_REJECTED,
            UmkmAccountClaim::STATUS_ACTIVATED,
        ], true)) {
            $query->where('status', $status);
        }

        return view('pages.admin-dinas.account-claims.index', [
            'claims' => $query->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(UmkmAccountClaim $claim)
    {
        $claim->load([
            'umkm:id,umkm_code,business_name',
            'submittedBy:id,name,email',
            'reviewedBy:id,name,email',
            'activatedUser:id,name,email,is_active',
            'events' => fn ($query) => $query->latest('event_time')->latest('id'),
        ]);

        return view('pages.admin-dinas.account-claims.show', [
            'claim' => $claim,
        ]);
    }

    public function inviteForm()
    {
        return view('pages.admin-dinas.account-claims.invite');
    }

    public function invite(
        InviteUmkmAccountClaimRequest $request,
        AccountClaimActivationService $service
    ) {
        $result = $service->createDinasInvite(
            $request->user(),
            $request->validated(),
            $request
        );

        $message = $result['delivery_ok']
            ? 'Undangan disetujui dan aktivasi telah dikirim ke email Pelaku.'
            : 'Undangan disetujui, tetapi email aktivasi gagal dikirim. Gunakan kirim ulang aktivasi.';

        return redirect()
            ->route('admin-dinas.account-claims.show', $result['claim'])
            ->with('status', $message);
    }

    public function review(
        ReviewUmkmAccountClaimRequest $request,
        UmkmAccountClaim $claim,
        AccountClaimActivationService $service
    ) {
        $result = $service->review(
            $request->user(),
            $claim,
            (string) $request->validated('action'),
            $request->validated('review_note'),
            $request
        );

        $message = $result['claim']->status === UmkmAccountClaim::STATUS_REJECTED
            ? 'Klaim ditolak dan histori tetap dipertahankan.'
            : ($result['delivery_ok']
                ? 'Klaim disetujui dan aktivasi telah dikirim ke email Pelaku.'
                : 'Klaim disetujui, tetapi email aktivasi gagal dikirim. Gunakan kirim ulang aktivasi.');

        return redirect()
            ->route('admin-dinas.account-claims.show', $result['claim'])
            ->with('status', $message);
    }

    public function resend(
        Request $request,
        UmkmAccountClaim $claim,
        AccountClaimActivationService $service
    ) {
        abort_unless($request->user()?->hasPermission('umkm.claim.review'), 403);

        $deliveryOk = $service->resendActivation($request->user(), $claim, $request);

        return back()->with(
            'status',
            $deliveryOk
                ? 'Aktivasi baru telah dikirim. Challenge sebelumnya dibatalkan.'
                : 'Challenge baru dibuat, tetapi email aktivasi gagal dikirim.'
        );
    }
}