<?php

namespace App\Http\Middleware\PelakuUmkm;

use App\Services\PelakuUmkm\PelakuWorkspaceAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedPelakuWorkspace
{
    public function __construct(
        private readonly PelakuWorkspaceAccessService $accessService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $this->accessService->canAccess($user)) {
            abort(403, 'Workspace Pelaku UMKM hanya tersedia untuk akun aktif dengan binding kepemilikan terverifikasi.');
        }

        return $next($request);
    }
}