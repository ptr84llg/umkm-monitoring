<?php

namespace App\Http\Middleware\Security;

use App\Models\Audit\SecurityEventLog;
use App\Services\Auth\SingleDeviceSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnforceSingleDeviceSession
{
    public function __construct(
        private readonly SingleDeviceSessionService $sessions
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->current_device_id) {
            return $next($request);
        }

        if ($this->sessions->sessionIsCurrent($user, $request)) {
            return $next($request);
        }

        try {
            SecurityEventLog::query()->create([
                'actor_user_id' => $user->id,
                'event_type' => 'single_device_session_rejected',
                'severity' => 'high',
                'event_detail' => 'Authenticated session rejected because another device/session is current.',
                'ip_address' => $request->ip(),
                'event_time' => now(),
            ]);
        } catch (Throwable) {
            // Security logging must not prevent session termination.
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()
            || $request->ajax()
            || $request->header('X-UMKM-Request') === 'internal') {
            return response()->json([
                'ok' => false,
                'message' => 'Sesi ini tidak lagi aktif. Silakan login kembali.',
                'force_relogin' => true,
                'redirect_url' => route('login'),
            ], 401);
        }

        return redirect()->route('login')
            ->with('status', 'Sesi ini tidak lagi aktif karena akun digunakan pada sesi lain. Silakan login kembali.');
    }
}