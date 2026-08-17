<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\AuthOAuthIdentity;
use App\Models\Audit\SecurityEventLog;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\OAuthIdentityService;
use App\Services\Auth\SingleDeviceSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleOAuthController extends Controller
{
    public function __construct(
        private readonly SingleDeviceSessionService $singleDeviceSessions
    ) {
    }

    private const PENDING_SESSION_KEY = 'auth.google.pending';

    private const PUBLIC_IDENTITY_SESSION_KEY = 'auth.google.public_identity';

    private const PENDING_TTL_MINUTES = 10;

    public function redirect(Request $request)
    {
        $request->session()->forget([
            self::PENDING_SESSION_KEY,
            self::PUBLIC_IDENTITY_SESSION_KEY,
        ]);

        $this->logGoogleEvent($request, 'google_oauth_redirect_started', 'low', 'Google OAuth redirect started.');

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, OAuthIdentityService $identityService, AuditLogger $auditLogger)
    {
        if ($request->filled('error')) {
            $this->logGoogleEvent(
                $request,
                'google_oauth_callback_cancelled',
                'low',
                'Google OAuth callback returned error='.$request->query('error')
            );

            return redirect()->route('login')->with('status', 'Proses masuk dengan Google dibatalkan.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            $this->logGoogleCallbackException($request, $exception);

            $this->logGoogleEvent(
                $request,
                'google_oauth_callback_failed',
                'medium',
                'Google OAuth callback failed before identity resolution. exception_class='.$exception::class
            );

            return redirect()->route('login')->with('status', 'Login Google belum dapat diproses. Silakan coba lagi.');
        }

        $stagedIdentity = $identityService->stageGooglePayload($this->googleUserToArray($googleUser), $request);

        if (! ($stagedIdentity['provider_email_verified'] ?? false)) {
            $this->logGoogleEvent(
                $request,
                'google_oauth_email_unverified_blocked',
                'medium',
                'Google OAuth blocked because provider email is not verified.'
            );

            return redirect()->route('login')->with('status', 'Login Google belum dapat diproses karena email Google belum terverifikasi.');
        }

        $existingIdentity = $identityService->findActiveGoogleIdentityByProviderId((string) $stagedIdentity['provider_id']);

        if ($existingIdentity && $existingIdentity->user_id) {
            $user = User::query()->find($existingIdentity->user_id);

            if (! $user || ! $user->isActive()) {
                $this->logGoogleEvent(
                    $request,
                    'google_oauth_linked_user_inactive_blocked',
                    'medium',
                    'Google OAuth blocked because linked user is missing or inactive.'
                );

                return redirect()->route('login')->with('status', 'Login Google belum dapat diproses.');
            }

            return $this->loginInternalUser($request, $auditLogger, $user, $existingIdentity);
        }

        $matchedUser = $identityService->findUserByVerifiedEmail($stagedIdentity['provider_email'] ?? null);

        if ($matchedUser && $matchedUser->isActive()) {
            $request->session()->put(self::PENDING_SESSION_KEY, [
                'staged_identity' => $stagedIdentity,
                'user_id' => $matchedUser->id,
                'expires_at' => now()->addMinutes(self::PENDING_TTL_MINUTES)->toIso8601String(),
            ]);

            $this->logGoogleEvent(
                $request,
                'google_oauth_pending_internal_link_created',
                'medium',
                'Google OAuth pending link created for user_id='.$matchedUser->id
            );

            return redirect()->route('login.google.link.confirm');
        }

        $identity = $identityService->storePublicLimitedGoogleIdentity($stagedIdentity, $request);

        $request->session()->put(self::PUBLIC_IDENTITY_SESSION_KEY, [
            'identity_id' => $identity->id,
            'provider_email' => $identity->provider_email,
            'provider_name' => $identity->provider_name,
            'notified_at' => now()->toIso8601String(),
        ]);

        $this->logGoogleEvent(
            $request,
            'google_oauth_public_limited_created',
            'medium',
            'Google OAuth public limited identity created for identity_id='.$identity->id
        );

        return redirect('/')->with('status', 'Akun Google dikenali sebagai akses publik terbatas. Dashboard internal belum tersedia.');
    }

    public function confirm(Request $request)
    {
        $pending = $this->validPendingIdentity($request);

        if (! $pending) {
            $request->session()->forget(self::PENDING_SESSION_KEY);

            return redirect()->route('login')->with('status', 'Sesi tautkan Google tidak tersedia atau sudah kedaluwarsa.');
        }

        return view('pages.auth.google-link-confirm', [
            'pendingEmail' => $this->maskEmail((string) ($pending['staged_identity']['provider_email'] ?? '')),
            'pendingName' => (string) ($pending['staged_identity']['provider_name'] ?? 'Akun Google'),
            'expiresAt' => (string) ($pending['expires_at'] ?? ''),
        ]);
    }

    public function link(Request $request, OAuthIdentityService $identityService, AuditLogger $auditLogger)
    {
        $pending = $this->validPendingIdentity($request);

        if (! $pending) {
            $request->session()->forget(self::PENDING_SESSION_KEY);

            return redirect()->route('login')->with('status', 'Sesi tautkan Google tidak tersedia atau sudah kedaluwarsa.');
        }

        $user = User::query()->find((int) $pending['user_id']);

        if (! $user || ! $user->isActive()) {
            $request->session()->forget(self::PENDING_SESSION_KEY);

            $this->logGoogleEvent(
                $request,
                'google_oauth_pending_link_user_invalid',
                'medium',
                'Google OAuth pending link blocked because user is missing or inactive.'
            );

            return redirect()->route('login')->with('status', 'Tautkan Google belum dapat diproses.');
        }

        try {
            $identity = $identityService->linkGoogleToInternalUser($user, $pending['staged_identity'], $request);
        } catch (Throwable) {
            $request->session()->forget(self::PENDING_SESSION_KEY);

            $this->logGoogleEvent(
                $request,
                'google_oauth_internal_link_failed',
                'medium',
                'Google OAuth internal link failed.'
            );

            return redirect()->route('login')->with('status', 'Tautkan Google belum dapat diproses.');
        }

        $request->session()->forget(self::PENDING_SESSION_KEY);

        $this->logGoogleEvent(
            $request,
            'google_oauth_internal_link_completed',
            'medium',
            'Google OAuth internal link completed for user_id='.$user->id
        );

        return $this->loginInternalUser($request, $auditLogger, $user, $identity);
    }

    public function cancel(Request $request, OAuthIdentityService $identityService)
    {
        $identityService->cancelPendingSession($request, self::PENDING_SESSION_KEY);

        $this->logGoogleEvent(
            $request,
            'google_oauth_pending_link_cancelled',
            'low',
            'Google OAuth pending link cancelled by user.'
        );

        return redirect()->route('login')->with('status', 'Proses tautkan Google dibatalkan.');
    }

    private function loginInternalUser(Request $request, AuditLogger $auditLogger, User $user, AuthOAuthIdentity $identity)
    {
        $access = $this->resolveInternalAccess($request, $user);

        if (! $access['allowed']) {
            $this->logGoogleEvent(
                $request,
                $access['event_type'],
                'medium',
                $access['event_detail']
            );

            return redirect('/')->with('status', 'Akun Google berhasil dikenali, tetapi dashboard internal belum tersedia.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->singleDeviceSessions->activate($user, $request, 'google', false, false);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $identity->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $auditLogger->log('google_login_success', $request, 'users', $user->id);

        return redirect()->to($access['redirect_url']);
    }

    private function resolveInternalAccess(Request $request, User $user): array
    {
        $roleAccessMap = [
            'admin_utama' => [
                'route' => 'admin-utama.dashboard',
                'permission' => 'dashboard.view.executive',
                'prefixes' => ['/admin-utama'],
            ],
            'admin_dinas' => [
                'route' => 'admin-dinas.dashboard',
                'permission' => 'umkm.read.official',
                'prefixes' => ['/admin-dinas'],
            ],
            'kepala_dinas' => [
                'route' => 'kepala-dinas.dashboard',
                'permission' => 'dashboard.view.executive',
                'prefixes' => ['/kepala-dinas'],
            ],
            'pelaku_umkm' => [
                'route' => 'pelaku-umkm.dashboard',
                'permission' => 'umkm.workspace.access',
                'prefixes' => ['/pelaku-umkm'],
            ],
            'validator_ahli' => [
                'route' => 'expert.validator.list',
                'permission' => 'validation.expert.fill',
                'prefixes' => ['/expert-validation/validator'],
            ],
        ];

        $activeRoleCodes = $user->roles()
            ->where('roles.is_active', true)
            ->pluck('code')
            ->filter()
            ->values()
            ->all();
        $missingRouteRole = null;

        if (count($activeRoleCodes) === 0) {
            return [
                'allowed' => false,
                'event_type' => 'google_login_without_role_limited',
                'event_detail' => 'Google login has no active internal role, redirected to public landing.',
            ];
        }

        foreach ($roleAccessMap as $role => $access) {
            if (! in_array($role, $activeRoleCodes, true)) {
                continue;
            }

            if (! Route::has($access['route'])) {
                $missingRouteRole = $role;

                continue;
            }

            if (! $user->hasPermission($access['permission'])) {
                return [
                    'allowed' => false,
                    'event_type' => 'google_login_missing_dashboard_permission_limited',
                    'event_detail' => "Google login role {$role} does not have required permission {$access['permission']}.",
                ];
            }

            $intendedUrl = $this->safeIntendedUrlForAccess($request, $access['prefixes']);

            return [
                'allowed' => true,
                'redirect_url' => $intendedUrl ?: route($access['route']),
            ];
        }

        if ($missingRouteRole !== null) {
            return [
                'allowed' => false,
                'event_type' => 'google_login_dashboard_route_inactive_limited',
                'event_detail' => "Google login recognized role {$missingRouteRole}, but its dashboard route is not active in current route scope.",
            ];
        }

        return [
            'allowed' => false,
            'event_type' => 'google_login_unsupported_role_limited',
            'event_detail' => 'Google login role is not registered as a dashboard login role.',
        ];
    }

    private function validPendingIdentity(Request $request): ?array
    {
        $pending = $request->session()->get(self::PENDING_SESSION_KEY);

        if (! is_array($pending)) {
            return null;
        }

        if (empty($pending['staged_identity']) || ! is_array($pending['staged_identity'])) {
            return null;
        }

        if (empty($pending['user_id']) || empty($pending['expires_at'])) {
            return null;
        }

        try {
            if (now()->greaterThan(\Illuminate\Support\Carbon::parse((string) $pending['expires_at']))) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        return $pending;
    }

    private function googleUserToArray($googleUser): array
    {
        return [
            'id' => $googleUser->getId(),
            'email' => $googleUser->getEmail(),
            'email_verified' => (bool) ($googleUser->user['email_verified'] ?? false),
            'name' => $googleUser->getName() ?: $googleUser->getNickname(),
            'avatar' => $googleUser->getAvatar(),
        ];
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return 'email Google';
        }

        [$name, $domain] = explode('@', $email, 2);

        $visible = substr($name, 0, 1);

        return $visible.str_repeat('*', max(3, strlen($name) - 1)).'@'.$domain;
    }

    private function safeIntendedUrlForAccess(Request $request, array $allowedPrefixes): ?string
    {
        $intendedUrl = $request->session()->pull('url.intended');

        if (! is_string($intendedUrl) || $intendedUrl === '') {
            return null;
        }

        if (! $this->isSafeLocalUrl($request, $intendedUrl)) {
            return null;
        }

        $path = $this->localPathFromUrl($request, $intendedUrl);

        foreach ($allowedPrefixes as $prefix) {
            $normalizedPrefix = rtrim($prefix, '/');

            if ($path === $normalizedPrefix || str_starts_with($path, $normalizedPrefix.'/')) {
                return $intendedUrl;
            }
        }

        return null;
    }

    private function localPathFromUrl(Request $request, string $url): string
    {
        if (str_starts_with($url, '/')) {
            $path = parse_url($url, PHP_URL_PATH);

            return is_string($path) && $path !== '' ? $path : '/';
        }

        $host = $request->getSchemeAndHttpHost();

        if (! str_starts_with($url, $host)) {
            return '/';
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    private function isSafeLocalUrl(Request $request, string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        return str_starts_with($url, $request->getSchemeAndHttpHost());
    }

    private function logGoogleCallbackException(Request $request, Throwable $exception): void
    {
        try {
            Log::warning('Google OAuth callback failed before identity resolution.', [
                'exception_class' => $exception::class,
                'exception_code' => $exception->getCode(),
                'exception_message' => substr($exception->getMessage(), 0, 240),
                'route' => optional($request->route())->getName(),
                'path' => $request->path(),
                'has_code_query' => $request->query->has('code'),
                'has_state_query' => $request->query->has('state'),
                'session_has_state' => $request->session()->has('state'),
                'google_redirect_configured' => (bool) config('services.google.redirect'),
                'google_client_id_configured' => (bool) config('services.google.client_id'),
                'ip_address' => $request->ip(),
                'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            ]);
        } catch (Throwable) {
            // Diagnostic logging failure must not expose implementation details.
        }
    }
    private function logGoogleEvent(Request $request, string $eventType, string $severity, string $detail): void
    {
        try {
            SecurityEventLog::query()->create([
                'actor_user_id' => null,
                'event_type' => $eventType,
                'severity' => $severity,
                'event_detail' => $detail,
                'ip_address' => $request->ip(),
                'event_time' => now(),
            ]);
        } catch (Throwable) {
            // Security logging failure must not expose implementation details.
        }
    }
}
