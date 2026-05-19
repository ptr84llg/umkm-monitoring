<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEventLog;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function create()
    {
        return view('pages.auth.login');
    }

    public function store(Request $request, AuditLogger $auditLogger)
    {
        $identifierField = $request->has('identifier') ? 'identifier' : 'email';

        $validated = $request->validate([
            $identifierField => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ], [
            $identifierField.'.required' => 'Identitas akun wajib diisi.',
            $identifierField.'.string' => 'Identitas akun tidak valid.',
            $identifierField.'.max' => 'Identitas akun terlalu panjang.',
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password tidak valid.',
        ]);

        $identifier = trim((string) ($validated[$identifierField] ?? ''));
        $password = (string) ($validated['password'] ?? '');

        $resolved = $this->resolveLoginIdentifier($identifier);
        $user = $resolved['user'];

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            SecurityEventLog::query()->create([
                'event_type' => 'failed_login',
                'severity' => 'medium',
                'event_detail' => 'Manual login failed using identifier type: '.$resolved['type'].'.',
                'ip_address' => $request->ip(),
                'event_time' => now(),
            ]);

            if ($this->expectsJson($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Login belum berhasil.',
                    'errors' => [
                        $identifierField => ['Kredensial tidak valid.'],
                    ],
                ]);
            }

            return back()->withErrors([
                $identifierField => 'Kredensial tidak valid.',
            ])->onlyInput($identifierField);
        }

        Auth::login($user);

        if (! $user->isActive()) {
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

        $this->markIdentifierUsed($resolved);

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

    private function resolveLoginIdentifier(string $identifier): array
    {
        $identifier = trim($identifier);
        $normalized = strtolower($identifier);

        if ($identifier === '') {
            return [
                'type' => 'empty',
                'user' => null,
                'credential_id' => null,
            ];
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return [
                'type' => 'email',
                'user' => User::query()
                    ->whereRaw('LOWER(email) = ?', [$normalized])
                    ->first(),
                'credential_id' => null,
            ];
        }

        if (preg_match('/^\d{16}$/', $identifier)) {
            $credential = DB::table('user_identity_credentials')
                ->where('identifier_type', 'nik')
                ->where('identifier_hash', $this->identifierHash($identifier))
                ->where('is_active', true)
                ->where('login_enabled', true)
                ->first();

            return [
                'type' => 'nik',
                'user' => $credential ? User::query()->find($credential->user_id) : null,
                'credential_id' => $credential?->id,
            ];
        }

        if (! preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $identifier)) {
            return [
                'type' => 'invalid',
                'user' => null,
                'credential_id' => null,
            ];
        }

        return [
            'type' => 'username',
            'user' => User::query()
                ->whereRaw('LOWER(username) = ?', [$normalized])
                ->first(),
            'credential_id' => null,
        ];
    }

    private function identifierHash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function markIdentifierUsed(array $resolved): void
    {
        if (($resolved['type'] ?? null) !== 'nik') {
            return;
        }

        if (empty($resolved['credential_id'])) {
            return;
        }

        DB::table('user_identity_credentials')
            ->where('id', $resolved['credential_id'])
            ->update([
                'last_used_at' => now(),
                'updated_at' => now(),
            ]);
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
         * Jangan invalidate/regenerateToken di respons AJAX login gagal setelah Auth::login().
         * Token CSRF halaman login harus tetap dapat dipakai agar user bisa memperbaiki akses/login
         * tanpa terkena 419 pada percobaan berikutnya.
         */
        $request->session()->regenerate();

        if ($this->expectsJson($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Login belum dapat dilanjutkan.',
                'errors' => [
                    $request->has('identifier') ? 'identifier' : 'email' => [$message],
                ],
            ]);
        }

        return back()->withErrors([
            $request->has('identifier') ? 'identifier' : 'email' => $message,
        ])->onlyInput($request->has('identifier') ? 'identifier' : 'email');
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
