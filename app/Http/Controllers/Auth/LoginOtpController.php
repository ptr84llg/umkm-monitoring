<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\LoginOtpService;
use App\Services\Auth\SingleDeviceSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class LoginOtpController extends Controller
{
    public function challenge(Request $request, LoginOtpService $loginOtpService)
    {
        $payload = $this->pendingPayload($request);
        $guard = $loginOtpService->activeChallengeForPayload($payload, $request);

        if (! $guard['ok']) {
            $request->session()->forget('auth.login_otp');

            return redirect()->route('login')->with('status', 'Silakan login ulang untuk memulai verifikasi akses.');
        }

        $challenge = $guard['challenge'];
        $resendAvailableAt = $loginOtpService->resendAvailableAtForPayload($payload, $challenge);

        return view('pages.auth.otp-challenge', [
            'challengeToken' => (string) $payload['challenge_token'],
            'maskedEmail' => (string) ($payload['masked_email'] ?? 'email akun'),
            'expiresAt' => $challenge->expires_at->toIso8601String(),
            'resendAvailableAt' => $resendAvailableAt->toIso8601String(),
        ]);
    }

    public function verify(Request $request, LoginOtpService $loginOtpService, AuditLogger $auditLogger, SingleDeviceSessionService $singleDeviceSessions)
    {
        $payload = $this->pendingPayload($request);
        $guard = $loginOtpService->activeChallengeForPayload($payload, $request);

        if (! $guard['ok']) {
            $request->session()->forget('auth.login_otp');

            return $this->safeOtpFailure($request, (string) $guard['message'], 422, true);
        }

        $validated = $request->validate([
            'challenge_token' => ['required', 'string', 'max:120'],
            'otp_code' => ['required', 'digits:6'],
        ], [
            'challenge_token.required' => 'Sesi verifikasi tidak valid.',
            'otp_code.required' => 'Kode OTP wajib diisi.',
            'otp_code.digits' => 'Kode OTP harus terdiri dari 6 digit.',
        ]);

        if (! hash_equals((string) $payload['challenge_token'], (string) $validated['challenge_token'])) {
            return $this->safeOtpFailure($request, 'Sesi verifikasi tidak sesuai. Silakan login ulang.', 422, true);
        }

        $user = User::query()->find((int) $payload['user_id']);

        if (! $user || ! $user->isActive()) {
            $request->session()->forget('auth.login_otp');

            return $this->safeOtpFailure($request, 'Sesi verifikasi tidak dapat dilanjutkan. Silakan login ulang.', 422, true);
        }

        $result = $loginOtpService->verifyLoginChallenge(
            $user,
            (string) $validated['challenge_token'],
            (string) $validated['otp_code'],
            $request
        );

        if (! $result['ok']) {
            return $this->safeOtpFailure($request, (string) $result['message']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $singleDeviceSessions->activate($user, $request, 'manual_otp', true, true);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $auditLogger->log('login_success', $request, 'users', $user->id);

        $redirectUrl = (string) ($payload['redirect_url'] ?? $this->fallbackRedirectUrl());
        $request->session()->forget('auth.login_otp');

        if ($this->expectsJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => 'Verifikasi berhasil. Mengalihkan ke dashboard.',
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl);
    }

    public function resend(Request $request, LoginOtpService $loginOtpService)
    {
        $payload = $this->pendingPayload($request);
        $guard = $loginOtpService->activeChallengeForPayload($payload, $request);

        if (! $guard['ok']) {
            $request->session()->forget('auth.login_otp');

            return $this->safeOtpFailure($request, (string) $guard['message'], 422, true);
        }

        $validated = $request->validate([
            'challenge_token' => ['required', 'string', 'max:120'],
        ], [
            'challenge_token.required' => 'Sesi verifikasi tidak valid.',
        ]);

        if (! hash_equals((string) $payload['challenge_token'], (string) $validated['challenge_token'])) {
            return $this->safeOtpFailure($request, 'Sesi verifikasi tidak sesuai. Silakan login ulang.', 422, true);
        }

        $cooldownSeconds = $loginOtpService->resendCooldownSecondsForPayload($payload, $guard['challenge']);

        if ($cooldownSeconds > 0) {
            $resendAvailableAt = $loginOtpService->resendAvailableAtForPayload($payload, $guard['challenge']);

            return $this->safeOtpFailure(
                $request,
                'Kode OTP baru belum dapat dikirim. Tunggu sampai hitung mundur selesai.',
                429,
                false,
                [
                    'resend_available_at' => $resendAvailableAt->toIso8601String(),
                    'resend_cooldown_seconds' => $cooldownSeconds,
                ]
            );
        }

        $user = User::query()->find((int) $payload['user_id']);

        if (! $user || ! $user->isActive()) {
            $request->session()->forget('auth.login_otp');

            return $this->safeOtpFailure($request, 'Sesi verifikasi tidak dapat dilanjutkan. Silakan login ulang.', 422, true);
        }

        $challenge = $loginOtpService->createLoginChallenge(
            $user,
            $request,
            (string) ($payload['redirect_url'] ?? $this->fallbackRedirectUrl()),
            true
        );

        $request->session()->put('auth.login_otp', $challenge['session_payload']);

        if ($this->expectsJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => 'Kode OTP baru sudah dikirim.',
                'challenge_token' => $challenge['session_payload']['challenge_token'],
                'masked_email' => $challenge['session_payload']['masked_email'],
                'expires_at' => $challenge['session_payload']['expires_at'],
                'resend_available_at' => $challenge['session_payload']['resend_available_at'],
            ]);
        }

        return back()->with('status', 'Kode OTP baru sudah dikirim.');
    }

    private function pendingPayload(Request $request): ?array
    {
        $payload = $request->session()->get('auth.login_otp');

        return is_array($payload) ? $payload : null;
    }

    private function safeOtpFailure(Request $request, string $message, int $status = 422, bool $forceRelogin = false, array $extra = [])
    {
        if ($this->expectsJson($request)) {
            return response()->json(array_merge([
                'ok' => false,
                'message' => $message,
                'force_relogin' => $forceRelogin,
                'redirect_url' => $forceRelogin ? route('login') : null,
                'errors' => [
                    'otp_code' => [$message],
                ],
            ], $extra), $status);
        }

        if ($forceRelogin) {
            return redirect()->route('login')->with('status', $message);
        }

        return back()->withErrors([
            'otp_code' => $message,
        ]);
    }

    private function expectsJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->header('X-UMKM-Request') === 'internal';
    }

    private function fallbackRedirectUrl(): string
    {
        return Route::has('admin-utama.dashboard')
            ? route('admin-utama.dashboard')
            : url('/');
    }
}
