<?php

namespace App\Http\Middleware;

use App\Models\SecurityEventLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUmkmInternalRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->headers->get('X-UMKM-Request', '');

        if ($header !== 'internal') {
            SecurityEventLog::query()->create([
                'actor_user_id' => $request->user()?->id,
                'event_type' => 'blocked_non_internal_request',
                'severity' => 'high',
                'event_detail' => 'Blocked request because X-UMKM-Request header was missing or invalid.',
                'ip_address' => $request->ip(),
                'event_time' => now(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Permintaan tidak valid.',
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
