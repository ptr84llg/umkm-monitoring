<?php

namespace App\Http\Controllers\PelakuUmkm;

use App\Http\Controllers\Controller;
use App\Http\Requests\PelakuUmkm\ActivateUmkmAccountClaimRequest;
use App\Http\Requests\PelakuUmkm\SubmitUmkmAccountClaimRequest;
use App\Models\Umkm\UmkmAccountClaim;
use App\Services\PelakuUmkm\AccountClaimActivationService;
use Illuminate\Http\Request;

class AccountClaimController extends Controller
{
    public function create()
    {
        return view('pages.pelaku-umkm.account-claim.create');
    }

    public function store(
        SubmitUmkmAccountClaimRequest $request,
        AccountClaimActivationService $service
    ) {
        $claim = $service->submitSelfClaim($request->validated(), $request);

        return redirect()
            ->route('pelaku-claim.status', ['claim_reference' => $claim->claim_reference])
            ->with('status', 'Pengajuan klaim diterima dan menunggu verifikasi Dinas.');
    }

    public function status(string $claimReference)
    {
        $claim = UmkmAccountClaim::query()
            ->where('claim_reference', $claimReference)
            ->firstOrFail();

        return view('pages.pelaku-umkm.account-claim.status', [
            'claim' => $claim,
        ]);
    }

    public function showActivation(
        Request $request,
        string $claimReference,
        AccountClaimActivationService $service
    ) {
        $claim = UmkmAccountClaim::query()
            ->where('claim_reference', $claimReference)
            ->firstOrFail();

        $token = (string) $request->query('token', '');
        $context = $service->activationPageData($claim, $token);

        abort_if($context === null, 404);

        return view('pages.pelaku-umkm.account-claim.activate', [
            'claim' => $claim,
            'activationToken' => $token,
            'activationContext' => $context,
        ]);
    }

    public function activate(
        ActivateUmkmAccountClaimRequest $request,
        string $claimReference,
        AccountClaimActivationService $service
    ) {
        $claim = UmkmAccountClaim::query()
            ->where('claim_reference', $claimReference)
            ->firstOrFail();

        $activated = $service->activate($claim, $request->validated(), $request);

        return redirect()
            ->route('pelaku-claim.status', ['claim_reference' => $activated->claim_reference])
            ->with(
                'status',
                'Aktivasi kredensial selesai. Akses workspace Pelaku tetap menunggu tahap ownership binding.'
            );
    }
}