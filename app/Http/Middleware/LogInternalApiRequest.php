<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogInternalApiRequest
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);

        try {
            ApiRequestLog::query()->create([
                'actor_user_id' => $request->user()?->id,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'http_status' => $response->getStatusCode(),
                'response_time_ms' => (int) ((microtime(true) - $start) * 1000),
                'ip_address' => $request->ip(),
                'origin' => $request->headers->get('Origin'),
                'requested_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Internal API request log failed.', [
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'actor_user_id' => $request->user()?->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        return $response;
    }
}
