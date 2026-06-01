<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionKeepAliveController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $now = now();

        $request->session()->put('umkm_last_keep_alive_at', $now->toIso8601String());
        $request->session()->put('umkm_last_activity_at', $now->timestamp);

        return response()->json([
            'ok' => true,
            'status' => 'active',
            'server_time' => $now->toIso8601String(),
            'session_lifetime_minutes' => (int) config('session.lifetime', 60),
            'warning_seconds' => (int) config('umkm.security.session_warning_seconds', 300),
        ]);
    }
}