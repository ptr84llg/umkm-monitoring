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
            SecurityEventLog::query()->create([
                'actor_user_id' => $user?->id,
                'event_type' => 'inactive_account_login_blocked',
                'severity' => 'medium',
                'event_detail' => 'Login blocked because user account is inactive.',
                'ip_address' => $request->ip(),
                'event_time' => now(),
            ]);

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($this->expectsJson($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Login belum berhasil.',
                    'errors' => [
                        'email' => ['Akun tidak aktif. Hubungi pengelola sistem.'],
                    ],
                ]);
            }

            return back()->withErrors([
                'email' => 'Akun tidak aktif. Hubungi pengelola sistem.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $auditLogger->log('login_success', $request, 'users', $user->id);

        $redirectUrl = $this->intendedOrRoleRedirect($request, $user);

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

    private function intendedOrRoleRedirect(Request $request, $user): string
    {
        $fallbackUrl = $this->roleRedirectUrl($user);
        $intendedUrl = $request->session()->pull('url.intended');

        if (! is_string($intendedUrl) || $intendedUrl === '') {
            return $fallbackUrl;
        }

        if (! $this->isSafeLocalUrl($request, $intendedUrl)) {
            return $fallbackUrl;
        }

        return $intendedUrl;
    }

    private function roleRedirectUrl($user): string
    {
        $roleRouteMap = [
            'admin_utama' => 'admin-utama.dashboard',
            'admin_dinas' => 'admin-dinas.dashboard',
            'kepala_dinas' => 'kepala-dinas.dashboard',
            'pelaku_umkm' => 'pelaku-umkm.dashboard',
            'validator_ahli' => 'expert.validator.list',
        ];

        foreach ($roleRouteMap as $role => $routeName) {
            if ($user->hasRole($role) && route($routeName, [], false)) {
                return route($routeName);
            }
        }

        if ($user->hasPermission('dashboard.view.executive')) {
            return route('dashboard.interactive');
        }

        SecurityEventLog::query()->create([
            'actor_user_id' => $user->id,
            'event_type' => 'login_without_dashboard_role',
            'severity' => 'medium',
            'event_detail' => 'User logged in but no dashboard role redirect was available.',
            'ip_address' => request()->ip(),
            'event_time' => now(),
        ]);

        return url('/');
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
