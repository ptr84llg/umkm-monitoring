<?php

use App\Http\Controllers\AdminDinas\AdminDinasController;
use App\Http\Controllers\AdminDinas\UmkmAccountClaimReviewController;
use App\Http\Controllers\PelakuUmkm\AccountClaimController;
use App\Http\Controllers\PelakuUmkm\PelakuUmkmController;
use App\Http\Controllers\AdminUtama\AdminUtamaController;
use App\Http\Controllers\Api\Public\LandingComponentController;
use App\Http\Controllers\Api\Public\LandingPreviewController;
use App\Http\Controllers\Api\Public\LandingRegionController;
use App\Http\Controllers\Api\Public\LandingRegionMapController;
use App\Http\Controllers\Api\Public\LocationGateController;
use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SessionKeepAliveController;
use App\Support\PublicLanding\PublicLandingData;
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
| Checkpoint 10C activates only the verified Pelaku UMKM read-only workspace.
| Proposal/profile override, survey, reporting, expert validation, export,
| smoke pages, and other deferred modules remain disabled at route level.
| Admin Dinas read-only scope and Checkpoint 10A claim review remain active.
|
*/

Route::get('/', fn () => view('pages.public.landing.index', [
    'publicLandingSummary' => PublicLandingData::summary(),
    'publicLandingHeroCards' => PublicLandingData::heroCards(),
    'publicLandingFooterMetrics' => PublicLandingData::footerMetrics(),
]));

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

Route::prefix('api/public/landing-region-map')
    ->name('public.landing-region-map.')
    ->middleware([
        'throttle:internal-sensitive',
        'validate.umkm.internal.request',
        'validate.internal.origin',
        'validate.internal.referer',
        'validate.fetch.metadata',
        'log.internal.api',
    ])
    ->group(function () {
        Route::get('/geometry', [LandingRegionMapController::class, 'geometry'])
            ->name('geometry');
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

Route::middleware('guest')->prefix('pelaku')->group(function () {
    Route::get('/claim', [AccountClaimController::class, 'create'])
        ->name('pelaku-claim.create');

    Route::post('/claim', [AccountClaimController::class, 'store'])
        ->middleware(['throttle:5,1', 'safe.errors'])
        ->name('pelaku-claim.store');

    Route::get('/claim/status/{claim_reference}', [AccountClaimController::class, 'status'])
        ->name('pelaku-claim.status');

    Route::get('/activation/{claim_reference}', [AccountClaimController::class, 'showActivation'])
        ->middleware('throttle:20,1')
        ->name('pelaku-activation.show');

    Route::post('/activation/{claim_reference}', [AccountClaimController::class, 'activate'])
        ->middleware(['throttle:8,1', 'safe.errors'])
        ->name('pelaku-activation.activate');
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

    Route::prefix('admin-dinas')
        ->name('admin-dinas.')
        ->middleware(['role:admin_dinas'])
        ->group(function () {
            Route::get('/dashboard', [AdminDinasController::class, 'dashboard'])
                ->middleware('permission:umkm.read.official')
                ->name('dashboard');

            Route::get('/umkm', [AdminDinasController::class, 'index'])
                ->middleware('permission:umkm.read.official')
                ->name('umkm.index');

            Route::get('/umkm/{umkm}', [AdminDinasController::class, 'show'])
                ->middleware('permission:umkm.read.official')
                ->name('umkm.show');

            Route::get('/analytics', [AdminDinasController::class, 'analytics'])
                ->middleware('permission:umkm.read.official')
                ->name('analytics.index');

            Route::get('/analytics/spatial', [AdminDinasController::class, 'spatialAnalytics'])
                ->middleware('permission:umkm.read.official')
                ->name('analytics.spatial');

            Route::get('/analytics/financial', [AdminDinasController::class, 'financialAnalytics'])
                ->middleware('permission:umkm.sensitive.financial')
                ->name('analytics.financial');

            Route::get('/account-claims/invite', [UmkmAccountClaimReviewController::class, 'inviteForm'])
                ->middleware('permission:umkm.claim.review')
                ->name('account-claims.invite');

            Route::post('/account-claims/invite', [UmkmAccountClaimReviewController::class, 'invite'])
                ->middleware(['permission:umkm.claim.review', 'throttle:10,1', 'safe.errors'])
                ->name('account-claims.invite.store');

            Route::get('/account-claims', [UmkmAccountClaimReviewController::class, 'index'])
                ->middleware('permission:umkm.claim.review')
                ->name('account-claims.index');

            Route::get('/account-claims/{claim}', [UmkmAccountClaimReviewController::class, 'show'])
                ->middleware('permission:umkm.claim.review')
                ->name('account-claims.show');

            Route::post('/account-claims/{claim}/review', [UmkmAccountClaimReviewController::class, 'review'])
                ->middleware(['permission:umkm.claim.review', 'throttle:10,1', 'safe.errors'])
                ->name('account-claims.review');

            Route::post('/account-claims/{claim}/resend-activation', [UmkmAccountClaimReviewController::class, 'resend'])
                ->middleware(['permission:umkm.claim.review', 'throttle:5,1', 'safe.errors'])
                ->name('account-claims.resend');
        });

    Route::prefix('pelaku-umkm')
        ->name('pelaku-umkm.')
        ->middleware([
            'role:pelaku_umkm',
            'permission:umkm.workspace.access',
            'pelaku.workspace.verified',
        ])
        ->group(function () {
            Route::get('/dashboard', [PelakuUmkmController::class, 'dashboard'])
                ->name('dashboard');

            Route::get('/umkm', [PelakuUmkmController::class, 'index'])
                ->name('umkm.index');

            Route::get('/umkm/{umkm}', [PelakuUmkmController::class, 'show'])
                ->name('umkm.show');
        });

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
