<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEventLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class PasswordResetController extends Controller
{
    private const SAFE_LINK_RESPONSE = 'Jika email terdaftar dan aktif, tautan pengaturan ulang password akan dikirim.';

    private const SAFE_RESET_ERROR = 'Permintaan pengaturan ulang password belum dapat diproses. Periksa kembali data yang dikirim.';

    public function create()
    {
        return view('pages.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email terlalu panjang.',
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $brokerStatus = 'skipped';

        if ($this->canReceiveResetLink($user)) {
            $brokerStatus = Password::sendResetLink(['email' => $email]);
        }

        $this->logPasswordEvent(
            $request,
            'password_reset_link_requested',
            'medium',
            'Password reset link requested. broker_status='.$brokerStatus.' user_found='.($user ? 'yes' : 'no')
        );

        if ($this->expectsJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => self::SAFE_LINK_RESPONSE,
            ]);
        }

        return back()->with('status', self::SAFE_LINK_RESPONSE);
    }

    public function edit(Request $request, string $token)
    {
        return view('pages.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'token.required' => 'Token pengaturan ulang tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email terlalu panjang.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password belum sesuai.',
        ]);

        $status = Password::reset(
            [
                'email' => strtolower(trim((string) $validated['email'])),
                'password' => (string) $validated['password'],
                'password_confirmation' => (string) $request->input('password_confirmation', ''),
                'token' => (string) $validated['token'],
            ],
            function (User $user, string $password) use ($request): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                $this->logPasswordEvent(
                    $request,
                    'password_reset_completed',
                    'medium',
                    'Password reset completed for user_id='.$user->id
                );
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $message = 'Password berhasil diperbarui. Silakan login kembali.';

            if ($this->expectsJson($request)) {
                return response()->json([
                    'ok' => true,
                    'message' => $message,
                    'redirect_url' => route('login'),
                ]);
            }

            return redirect()->route('login')->with('status', $message);
        }

        $this->logPasswordEvent(
            $request,
            'password_reset_failed',
            'medium',
            'Password reset failed. broker_status='.$status
        );

        if ($this->expectsJson($request)) {
            return response()->json([
                'ok' => false,
                'message' => self::SAFE_RESET_ERROR,
                'errors' => [
                    'email' => [self::SAFE_RESET_ERROR],
                ],
            ], 422);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => self::SAFE_RESET_ERROR]);
    }

    private function canReceiveResetLink(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isActive')) {
            return (bool) $user->isActive();
        }

        return (bool) ($user->is_active ?? false);
    }

    private function logPasswordEvent(Request $request, string $eventType, string $severity, string $detail): void
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
            // Logging failure must not expose implementation details to the requester.
        }
    }

    private function expectsJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->header('X-UMKM-Request') === 'internal';
    }
}
