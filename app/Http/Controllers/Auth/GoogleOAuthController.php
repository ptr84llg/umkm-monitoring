<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthOAuthIdentity;
use App\Models\SecurityEventLog;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\OAuthIdentityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleOAuthController extends Controller
{
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
        } catch (Throwable) {
            $this->logGoogleEvent(
                $request,
                'google_oauth_callback_failed',
                'medium',
                'Google OAuth callback failed before identity resolution.'
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

            return redirect()->route('login')->with('status', 'Email Google cocok dengan akun terdaftar. Konfirmasi tautkan Google akan diaktifkan pada tahap berikutnya.');
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
                'permission' => 'umkm.submit.update',
                'prefixes' => ['/pelaku-umkm', '/proposals', '/survey'],
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

        return [
            'allowed' => false,
            'event_type' => 'google_login_unsupported_role_limited',
            'event_detail' => 'Google login role is not registered as a dashboard login role.',
        ];
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