<?php

namespace App\Http\Middleware;

use App\Models\SecurityEventLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureLocationGateVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('umkm.location_gate.enabled', true)) {
            return $next($request);
        }

        if ($this->isVerified($request)) {
            return $next($request);
        }

        SecurityEventLog::query()->create([
            'actor_user_id' => $request->user()?->id,
            'event_type' => 'blocked_login_without_location_gate',
            'severity' => 'medium',
            'event_detail' => 'Login route access blocked because location gate session was missing or invalid.',
            'ip_address' => $request->ip(),
            'event_time' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => 'Akses login membutuhkan validasi lokasi dari halaman awal.',
            ], 403);
        }

        return redirect('/')
            ->with('location_gate_required', true);
    }

    private function isVerified(Request $request): bool
    {
        $session = $request->session()->get($this->sessionKey());

        if (! is_array($session)) {
            return false;
        }

        if (($session['verified'] ?? false) !== true) {
            return false;
        }

        $expiresAt = $session['expires_at'] ?? null;

        if (! $expiresAt) {
            $request->session()->forget($this->sessionKey());

            return false;
        }

        try {
            $expiresAt = Carbon::parse($expiresAt);
        } catch (Throwable) {
            $request->session()->forget($this->sessionKey());

            return false;
        }

        if ($expiresAt->isPast()) {
            $request->session()->forget($this->sessionKey());

            return false;
        }

        $expectedIpHash = $this->fingerprint($request->ip());
        $expectedAgentHash = $this->fingerprint($request->userAgent() ?? '');

        return hash_equals($expectedIpHash, (string) ($session['ip_hash'] ?? ''))
            && hash_equals($expectedAgentHash, (string) ($session['user_agent_hash'] ?? ''));
    }

    private function sessionKey(): string
    {
        return (string) config('umkm.location_gate.session_key', 'umkm.location_gate');
    }

    private function fingerprint(?string $value): string
    {
        return hash_hmac('sha256', (string) $value, (string) config('app.key'));
    }
}
