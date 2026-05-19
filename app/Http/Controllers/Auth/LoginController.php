<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEventLog;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('pages.auth.login');
    }

    public function store(Request $request, AuditLogger $auditLogger)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Email harus menggunakan format yang valid.',
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password tidak valid.',
        ]);

        if (! Auth::attempt($credentials)) {
            SecurityEventLog::query()->create([
                'event_type' => 'failed_login',
                'severity' => 'medium',
                'event_detail' => 'Internal login failed',
                'ip_address' => $request->ip(),
                'event_time' => now(),
            ]);

            if ($this->expectsJson($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Login belum berhasil.',
                    'errors' => [
                        'email' => ['Kredensial tidak valid.'],
                    ],
                ]);
            }

            return back()->withErrors([
                'email' => 'Kredensial tidak valid.',
            ])->onlyInput('email');
        }

        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            return $this->denyAuthenticatedLogin(
                $request,
                $user,
                'inactive_account_login_blocked',
                'Akun tidak aktif. Hubungi pengelola sistem.',
                'Login blocked because user account is inactive.'
            );
        }

        $access = $this->resolveManualLoginAccess($request, $user);

        if (! $access['allowed']) {
            return $this->denyAuthenticatedLogin(
                $request,
                $user,
                $access['event_type'],
                $access['message'],
                $access['event_detail']
            );
        }

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $auditLogger->log('login_success', $request, 'users', $user->id);

        $redirectUrl = $access['redirect_url'];

        if ($this->expectsJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => 'Login berhasil.',
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl);
    }

    public function destroy(Request $request, AuditLogger $auditLogger)
    {
        $auditLogger->log('logout', $request, 'users', $request->user()?->id);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function resolveManualLoginAccess(Request $request, $user): array
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
                'event_type' => 'manual_login_without_role_blocked',
                'event_detail' => 'Manual login blocked because user has no active role. No-role accounts are reserved for Google limited access flow.',
                'message' => 'Akun berhasil dikenali, tetapi akses sistem belum diaktifkan. Silakan hubungi pengelola sistem.',
            ];
        }

        foreach ($roleAccessMap as $role => $access) {
            if (! in_array($role, $activeRoleCodes, true)) {
                continue;
            }

            if (! $user->hasPermission($access['permission'])) {
                return [
                    'allowed' => false,
                    'event_type' => 'manual_login_missing_dashboard_permission_blocked',
                    'event_detail' => "Manual login blocked because role {$role} does not have required permission {$access['permission']}.",
                    'message' => 'Akun berhasil dikenali, tetapi hak akses dashboard belum lengkap. Silakan hubungi pengelola sistem.',
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
            'event_type' => 'manual_login_unsupported_role_blocked',
            'event_detail' => 'Manual login blocked because user role is not registered as a dashboard login role.',
            'message' => 'Akun berhasil dikenali, tetapi role akun belum terdaftar sebagai akses login sistem. Silakan hubungi pengelola sistem.',
        ];
    }

    private function denyAuthenticatedLogin(Request $request, $user, string $eventType, string $message, string $eventDetail)
    {
        SecurityEventLog::query()->create([
            'actor_user_id' => $user?->id,
            'event_type' => $eventType,
            'severity' => 'medium',
            'event_detail' => $eventDetail,
            'ip_address' => $request->ip(),
            'event_time' => now(),
        ]);

        Auth::logout();

        /*
         * Jangan invalidate/regenerateToken di respons AJAX login gagal setelah Auth::attempt().
         * Token CSRF halaman login harus tetap dapat dipakai agar user bisa memperbaiki akses/login
         * tanpa terkena 419 pada percobaan berikutnya.
         */
        $request->session()->regenerate();

        if ($this->expectsJson($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Login belum dapat dilanjutkan.',
                'errors' => [
                    'email' => [$message],
                ],
            ]);
        }

        return back()->withErrors([
            'email' => $message,
        ])->onlyInput('email');
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

            if ($path === $normalizedPrefix || str_starts_with($path, $normalizedPrefix . '/')) {
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

    private function expectsJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->header('X-UMKM-Request') === 'internal';
    }
}
