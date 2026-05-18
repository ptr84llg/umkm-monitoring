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

        $redirectUrl = redirect()->intended('/dashboard/interaktif')->getTargetUrl();

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

    private function expectsJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->header('X-UMKM-Request') === 'internal';
    }
}
