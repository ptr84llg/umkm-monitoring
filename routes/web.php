<?php

use App\Http\Controllers\AdminUtama\AdminUtamaController;
use App\Http\Controllers\Api\Public\LandingComponentController;
use App\Http\Controllers\Api\Public\LandingPreviewController;
use App\Http\Controllers\Api\Public\LandingRegionController;
use App\Http\Controllers\Api\Public\LocationGateController;
use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SessionKeepAliveController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Active Scope Routes
|--------------------------------------------------------------------------
|
| Scope-2A intentionally keeps only the routes that match the current locked
| development stage:
|
| 1. public landing;
| 2. public landing AJAX components;
| 3. public location gate;
| 4. login/logout;
| 5. simple admin utama dashboard.
|
| Routes for admin dinas, pelaku UMKM, kepala dinas, survey, expert validation,
| export, proposal, dashboard analytics, smoke pages, and internal API modules
| are temporarily disabled at route level. Their controllers, views, models,
| services, policies, assets, and other foundations are not deleted.
|
*/

Route::get('/', fn () => view('landing'));

Route::prefix('api/public/landing-components')
    ->name('public.landing-components.')
    ->middleware([
        'throttle:internal-sensitive',
        'validate.umkm.internal.request',
        'validate.internal.origin',
        'validate.internal.referer',
        'validate.fetch.metadata',
        'log.internal.api',
    ])
    ->group(function () {
        Route::get('/hero-preview-board', [LandingComponentController::class, 'heroPreviewBoard'])
            ->name('hero-preview-board');

        Route::get('/dashboard-preview', [LandingComponentController::class, 'dashboardPreview'])
            ->name('dashboard-preview');

        Route::get('/summary-section', [LandingComponentController::class, 'summarySection'])
            ->name('summary-section');

        Route::get('/cta-section', [LandingComponentController::class, 'ctaSection'])
            ->name('cta-section');

        Route::get('/region-modal', [LandingComponentController::class, 'regionModal'])
            ->name('region-modal');
    });

Route::prefix('api/public/landing-regions')
    ->middleware([
        'throttle:internal-sensitive',
        'validate.umkm.internal.request',
        'validate.internal.origin',
        'validate.internal.referer',
        'validate.fetch.metadata',
        'log.internal.api',
    ])
    ->group(function () {
        Route::get('/context', [LandingRegionController::class, 'context'])
            ->name('landing.regions.context');

        Route::get('/children', [LandingRegionController::class, 'children'])
            ->name('landing.regions.children');
    });

Route::prefix('api/public/landing-preview')
    ->name('public.landing-preview.')
    ->middleware([
        'throttle:internal-sensitive',
        'validate.umkm.internal.request',
        'validate.internal.origin',
        'validate.internal.referer',
        'validate.fetch.metadata',
        'log.internal.api',
    ])
    ->group(function () {
        Route::get('/data', [LandingPreviewController::class, 'data'])
            ->name('data');
    });

Route::prefix('api/public/location-gate')
    ->name('public.location-gate.')
    ->middleware([
        'throttle:internal-sensitive',
        'validate.umkm.internal.request',
        'validate.internal.origin',
        'validate.internal.referer',
        'validate.fetch.metadata',
        'log.internal.api',
    ])
    ->group(function () {
        Route::post('/verify', [LocationGateController::class, 'verify'])
            ->name('verify');

        Route::post('/clear', [LocationGateController::class, 'clear'])
            ->name('clear');
    });

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->middleware('location.gate')
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware([
            'throttle:login',
            'safe.errors',
            'validate.umkm.internal.request',
            'validate.internal.origin',
            'validate.internal.referer',
            'validate.fetch.metadata',
            'anti.bot',
            'location.gate',
            'log.internal.api',
        ])
        ->name('login.store');

    Route::get('/login/google', [GoogleOAuthController::class, 'redirect'])
        ->middleware('location.gate')
        ->name('login.google.redirect');

    Route::get('/login/google/callback', [GoogleOAuthController::class, 'callback'])
        ->name('login.google.callback');

    Route::get('/login/google/link', [GoogleOAuthController::class, 'confirm'])
        ->middleware('location.gate')
        ->name('login.google.link.confirm');

    Route::post('/login/google/link', [GoogleOAuthController::class, 'link'])
        ->middleware([
            'throttle:login',
            'safe.errors',
            'location.gate',
            'log.internal.api',
        ])
        ->name('login.google.link');

    Route::post('/login/google/cancel', [GoogleOAuthController::class, 'cancel'])
        ->middleware([
            'throttle:login',
            'safe.errors',
            'location.gate',
            'log.internal.api',
        ])
        ->name('login.google.cancel');
});

Route::middleware('guest')->group(function () {
    Route::get('/login/otp', [LoginOtpController::class, 'challenge'])
        ->middleware('location.gate')
        ->name('login.otp.challenge');

    Route::post('/login/otp', [LoginOtpController::class, 'verify'])
        ->middleware([
            'throttle:login.otp.verify',
            'safe.errors',
            'validate.umkm.internal.request',
            'validate.internal.origin',
            'validate.internal.referer',
            'validate.fetch.metadata',
            'anti.bot',
            'location.gate',
            'log.internal.api',
        ])
        ->name('login.otp.verify');

    Route::post('/login/otp/resend', [LoginOtpController::class, 'resend'])
        ->middleware([
            'throttle:login.otp.resend',
            'safe.errors',
            'validate.umkm.internal.request',
            'validate.internal.origin',
            'validate.internal.referer',
            'validate.fetch.metadata',
            'anti.bot',
            'location.gate',
            'log.internal.api',
        ])
        ->name('login.otp.resend');
});
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])
        ->middleware('location.gate')
        ->name('password.request');

    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware([
            'throttle:password.email',
            'safe.errors',
            'validate.umkm.internal.request',
            'validate.internal.origin',
            'validate.internal.referer',
            'validate.fetch.metadata',
            'anti.bot',
            'location.gate',
            'log.internal.api',
        ])
        ->name('password.email');

    Route::get('/reset-password/otp', [PasswordResetController::class, 'otpChallenge'])
        ->middleware('location.gate')
        ->name('password.otp.challenge');

    Route::post('/reset-password/otp', [PasswordResetController::class, 'verifyOtp'])
        ->middleware([
            'throttle:login.otp.verify',
            'safe.errors',
            'validate.umkm.internal.request',
            'validate.internal.origin',
            'validate.internal.referer',
            'validate.fetch.metadata',
            'anti.bot',
            'location.gate',
            'log.internal.api',
        ])
        ->name('password.otp.verify');

    Route::post('/reset-password/otp/resend', [PasswordResetController::class, 'resendOtp'])
        ->middleware([
            'throttle:login.otp.resend',
            'safe.errors',
            'validate.umkm.internal.request',
            'validate.internal.origin',
            'validate.internal.referer',
            'validate.fetch.metadata',
            'anti.bot',
            'location.gate',
            'log.internal.api',
        ])
        ->name('password.otp.resend');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])
        ->middleware('location.gate')
        ->name('password.reset');

    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->middleware([
            'throttle:password.update',
            'safe.errors',
            'validate.umkm.internal.request',
            'validate.internal.origin',
            'validate.internal.referer',
            'validate.fetch.metadata',
            'anti.bot',
            'location.gate',
            'log.internal.api',
        ])
        ->name('password.update');

});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::post('/session/keep-alive', SessionKeepAliveController::class)
        ->middleware([
            'throttle:internal-sensitive',
            'safe.errors',
            'validate.umkm.internal.request',
            'validate.internal.origin',
            'validate.internal.referer',
            'validate.fetch.metadata',
            'log.internal.api',
        ])
        ->name('session.keep-alive');

    Route::prefix('admin-utama')
        ->name('admin-utama.')
        ->middleware(['role:admin_utama'])
        ->group(function () {
            Route::get('/dashboard', [AdminUtamaController::class, 'dashboard'])
                ->middleware('permission:dashboard.view.executive')
                ->name('dashboard');

            Route::get('/access', [AdminUtamaController::class, 'accessIndex'])
                ->middleware('permission:access.manage')
                ->name('access.index');

            Route::get('/governance/settings', [AdminUtamaController::class, 'settings'])
                ->middleware('permission:system.manage')
                ->name('governance.settings');

            Route::post('/governance/settings/theme', [AdminUtamaController::class, 'updateTheme'])
                ->middleware([
                    'permission:system.manage',
                    'throttle:internal-sensitive',
                    'safe.errors',
                    'validate.umkm.internal.request',
                    'validate.internal.origin',
                    'validate.internal.referer',
                    'validate.fetch.metadata',
                    'log.internal.api',
                ])
                ->name('governance.settings.theme');        });
});
