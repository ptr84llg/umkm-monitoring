<?php

use App\Http\Controllers\AdminUtama\AdminUtamaController;
use App\Http\Controllers\Api\Public\LandingComponentController;
use App\Http\Controllers\Api\Public\LandingPreviewController;
use App\Http\Controllers\Api\Public\LandingRegionController;
use App\Http\Controllers\Api\Public\LocationGateController;
use App\Http\Controllers\Auth\LoginController;
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
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::prefix('admin-utama')
        ->name('admin-utama.')
        ->middleware(['role:admin_utama'])
        ->group(function () {
            Route::get('/dashboard', [AdminUtamaController::class, 'dashboard'])
                ->middleware('permission:dashboard.view.executive')
                ->name('dashboard');
        });
});
