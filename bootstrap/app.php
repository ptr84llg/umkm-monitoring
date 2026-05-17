<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api-internal.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'validate.internal.origin' => \App\Http\Middleware\ValidateInternalOrigin::class,
            'validate.internal.referer' => \App\Http\Middleware\ValidateInternalReferer::class,
            'validate.fetch.metadata' => \App\Http\Middleware\ValidateFetchMetadata::class,
            'log.internal.api' => \App\Http\Middleware\LogInternalApiRequest::class,
            'secure.headers' => \App\Http\Middleware\SecureHeaders::class,
            'anti.bot' => \App\Http\Middleware\AntiBotGuard::class,
            'safe.errors' => \App\Http\Middleware\SafeErrorResponder::class,
        ]);

        $middleware->redirectGuestsTo('/');

        $middleware->web(append: [
            \App\Http\Middleware\SecureHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->booted(function (): void {
        RateLimiter::for('internal-sensitive', function (Request $request) {
            return Limit::perMinutes(
                (int) config('umkm.security.api_rate_window', 1),
                (int) config('umkm.security.api_rate_limit', 60)
            )->by($request->user()?->id ?: $request->ip());
        });
    })->create();
