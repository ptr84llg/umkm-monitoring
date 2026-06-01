<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEventLog;
use App\Models\User;
use App\Services\Auth\LoginOtpService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class PasswordResetController extends Controller
{
    private const SAFE_LINK_RESPONSE = 'Jika email terdaftar dan aktif, tautan pengaturan ulang password akan dikirim.';
    private const SAFE_RESET_ERROR = 'Permintaan pengaturan ulang password belum dapat diproses. Periksa kembali data yang dikirim.';
    private const SAFE_OTP_REQUIRED = 'Verifikasi OTP diperlukan sebelum password disimpan.';

    public function create()
    {
        return view('pages.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:190']]);
        $email = strtolower(trim((string) $validated['email']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $brokerStatus = 'skipped';

        if ($this->canReceiveResetLink($user)) {
            $brokerStatus = Password::sendResetLink(['email' => $email]);
        }

        $this->logPasswordEvent($request, 'password_reset_link_requested', 'medium', 'Password reset link requested. broker_status='.$brokerStatus.' user_found='.($user ? 'yes' : 'no'));

        if ($this->expectsJson($request)) {
            return response()->json(['ok' => true, 'message' => self::SAFE_LINK_RESPONSE]);
        }

        return back()->with('status', self::SAFE_LINK_RESPONSE);
    }

    public function edit(Request $request, string $token)
    {
        $context = $this->resetContext($request, $token, (string) $request->query('email', ''));

        if (! $context['ok']) {
            $this->logPasswordEvent($request, 'password_reset_link_invalid_or_expired', 'medium', 'Password reset form blocked because reset link is invalid or expired.');

            return view('pages.auth.reset-password', [
                'token' => '',
                'email' => '',
                'linkInvalid' => true,
                'expiresAt' => null,
                'expiredTitle' => 'Tautan Reset Tidak Berlaku',
                'expiredMessage' => 'Tautan pengaturan ulang password tidak valid atau sudah melewati batas waktu. Silakan minta tautan baru.',
            ]);
        }

        return view('pages.auth.reset-password', [
            'token' => $token,
            'email' => $context['email'],
            'linkInvalid' => false,
            'expiresAt' => $context['expires_at']->toIso8601String(),
            'expiresAtLabel' => $context['expires_at']->format('H:i'),
        ]);
    }

    public function update(Request $request, LoginOtpService $loginOtpService)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $context = $this->resetContext($request, (string) $validated['token'], (string) $validated['email']);

        if (! $context['ok']) {
            return $this->safeResetFailure($request, 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.', 422);
        }

        /** @var User $user */
        $user = $context['user'];
        $challenge = $loginOtpService->createPasswordResetChallenge($user, $request);

        $request->session()->put('auth.password_reset_otp', array_merge($challenge['session_payload'], [
            'email' => $context['email'],
            'reset_token' => (string) $validated['token'],
            'password_encrypted' => Crypt::encryptString((string) $validated['password']),
            'password_confirmation_encrypted' => Crypt::encryptString((string) $request->input('password_confirmation', '')),
            'reset_link_expires_at' => $context['expires_at']->toIso8601String(),
        ]));

        $this->logPasswordEvent($request, 'password_reset_otp_required', 'medium', 'Password reset OTP challenge created for user_id='.$user->id);

        if ($this->expectsJson($request)) {
            return response()->json(['ok' => true, 'message' => self::SAFE_OTP_REQUIRED, 'redirect_url' => route('password.otp.challenge')]);
        }

        return redirect()->route('password.otp.challenge')->with('status', self::SAFE_OTP_REQUIRED);
    }

    public function otpChallenge(Request $request, LoginOtpService $loginOtpService)
    {
        $payload = $this->pendingPasswordResetPayload($request);
        $guard = $loginOtpService->activeChallengeForPayload($payload, $request, LoginOtpService::PURPOSE_PASSWORD_RESET);

        if (! $guard['ok']) {
            $request->session()->forget('auth.password_reset_otp');

            return redirect()->route('password.request')->with('status', 'Silakan minta tautan reset password baru untuk memulai verifikasi.');
        }

        $context = $this->resetContext($request, (string) ($payload['reset_token'] ?? ''), (string) ($payload['email'] ?? ''));

        if (! $context['ok']) {
            $request->session()->forget('auth.password_reset_otp');

            return redirect()->route('password.request')->with('status', 'Tautan reset password tidak berlaku lagi. Silakan minta tautan baru.');
        }

        $challenge = $guard['challenge'];
        $resendAvailableAt = $loginOtpService->resendAvailableAtForPayload($payload, $challenge);

        return view('pages.auth.otp-challenge', [
            'otpContext' => 'password_reset',
            'challengeToken' => (string) $payload['challenge_token'],
            'maskedEmail' => (string) ($payload['masked_email'] ?? 'email akun'),
            'expiresAt' => $challenge->expires_at->toIso8601String(),
            'resendAvailableAt' => $resendAvailableAt->toIso8601String(),
            'verifyAction' => route('password.otp.verify'),
            'resendAction' => route('password.otp.resend'),
            'returnUrl' => route('password.request'),
            'returnLabel' => 'Minta tautan baru',
            'brandSubtitle' => 'Verifikasi Reset Password',
            'subtitle' => 'Kode OTP dikirim ke '.$payload['masked_email'].'. Gunakan kode terbaru untuk menyelesaikan reset password.',
            'submitLabel' => 'Verifikasi dan Simpan Password',
            'successTitle' => 'Password diperbarui',
            'successMessage' => 'Password berhasil diperbarui. Silakan login kembali.',
            'loadingTitle' => 'Memverifikasi OTP',
            'loadingMessage' => 'Sistem sedang memvalidasi OTP reset password.',
            'resendLoadingTitle' => 'Mengirim OTP Baru',
            'resendLoadingMessage' => 'Sistem sedang mengirim ulang kode OTP reset password.',
            'note' => 'Verifikasi OTP hanya aktif setelah tautan reset password valid dan password baru sudah dikirim. Jika halaman ini dibuka tanpa tahapan yang sah, sistem akan mengarahkan kembali ke proses reset.',
        ]);
    }

    public function verifyOtp(Request $request, LoginOtpService $loginOtpService)
    {
        $payload = $this->pendingPasswordResetPayload($request);
        $guard = $loginOtpService->activeChallengeForPayload($payload, $request, LoginOtpService::PURPOSE_PASSWORD_RESET);

        if (! $guard['ok']) {
            $request->session()->forget('auth.password_reset_otp');

            return $this->safeOtpFailure($request, (string) $guard['message'], 422, true);
        }

        $validated = $request->validate([
            'challenge_token' => ['required', 'string', 'max:120'],
            'otp_code' => ['required', 'digits:6'],
        ]);

        if (! hash_equals((string) ($payload['challenge_token'] ?? ''), (string) $validated['challenge_token'])) {
            return $this->safeOtpFailure($request, 'Sesi verifikasi tidak sesuai. Silakan mulai ulang proses reset password.', 422, true);
        }

        $context = $this->resetContext($request, (string) ($payload['reset_token'] ?? ''), (string) ($payload['email'] ?? ''));

        if (! $context['ok']) {
            $request->session()->forget('auth.password_reset_otp');

            return $this->safeOtpFailure($request, 'Tautan reset password tidak berlaku lagi. Silakan minta tautan baru.', 422, true);
        }

        /** @var User $user */
        $user = $context['user'];
        $result = $loginOtpService->verifyPasswordResetChallenge($user, (string) $validated['challenge_token'], (string) $validated['otp_code'], $request);

        if (! $result['ok']) {
            return $this->safeOtpFailure($request, (string) $result['message']);
        }

        try {
            $password = Crypt::decryptString((string) ($payload['password_encrypted'] ?? ''));
            $passwordConfirmation = Crypt::decryptString((string) ($payload['password_confirmation_encrypted'] ?? ''));
        } catch (Throwable) {
            $request->session()->forget('auth.password_reset_otp');

            return $this->safeOtpFailure($request, 'Data reset password tidak dapat dibaca. Silakan mulai ulang proses reset.', 422, true);
        }

        $status = Password::reset(
            [
                'email' => $context['email'],
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
                'token' => (string) $payload['reset_token'],
            ],
            function (User $user, string $password) use ($request): void {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();

                event(new PasswordReset($user));

                $this->logPasswordEvent($request, 'password_reset_completed', 'medium', 'Password reset completed after OTP verification for user_id='.$user->id);
            }
        );

        $request->session()->forget('auth.password_reset_otp');

        if ($status === Password::PASSWORD_RESET) {
            $message = 'Password berhasil diperbarui. Silakan login kembali.';

            if ($this->expectsJson($request)) {
                return response()->json(['ok' => true, 'message' => $message, 'redirect_url' => route('login')]);
            }

            return redirect()->route('login')->with('status', $message);
        }

        $this->logPasswordEvent($request, 'password_reset_failed_after_otp', 'medium', 'Password reset failed after OTP. broker_status='.$status);

        return $this->safeOtpFailure($request, self::SAFE_RESET_ERROR, 422, true);
    }

    public function resendOtp(Request $request, LoginOtpService $loginOtpService)
    {
        $payload = $this->pendingPasswordResetPayload($request);
        $guard = $loginOtpService->activeChallengeForPayload($payload, $request, LoginOtpService::PURPOSE_PASSWORD_RESET);

        if (! $guard['ok']) {
            $request->session()->forget('auth.password_reset_otp');

            return $this->safeOtpFailure($request, (string) $guard['message'], 422, true);
        }

        $validated = $request->validate(['challenge_token' => ['required', 'string', 'max:120']]);

        if (! hash_equals((string) ($payload['challenge_token'] ?? ''), (string) $validated['challenge_token'])) {
            return $this->safeOtpFailure($request, 'Sesi verifikasi tidak sesuai. Silakan mulai ulang proses reset password.', 422, true);
        }

        $context = $this->resetContext($request, (string) ($payload['reset_token'] ?? ''), (string) ($payload['email'] ?? ''));

        if (! $context['ok']) {
            $request->session()->forget('auth.password_reset_otp');

            return $this->safeOtpFailure($request, 'Tautan reset password tidak berlaku lagi. Silakan minta tautan baru.', 422, true);
        }

        $cooldownSeconds = $loginOtpService->resendCooldownSecondsForPayload($payload, $guard['challenge']);

        if ($cooldownSeconds > 0) {
            $resendAvailableAt = $loginOtpService->resendAvailableAtForPayload($payload, $guard['challenge']);

            return $this->safeOtpFailure(
                $request,
                'Kode OTP baru belum dapat dikirim. Tunggu sampai hitung mundur selesai.',
                429,
                false,
                ['resend_available_at' => $resendAvailableAt->toIso8601String(), 'resend_cooldown_seconds' => $cooldownSeconds]
            );
        }

        /** @var User $user */
        $user = $context['user'];
        $challenge = $loginOtpService->createPasswordResetChallenge($user, $request, true);

        $request->session()->put('auth.password_reset_otp', array_merge($challenge['session_payload'], [
            'email' => $payload['email'],
            'reset_token' => $payload['reset_token'],
            'password_encrypted' => $payload['password_encrypted'],
            'password_confirmation_encrypted' => $payload['password_confirmation_encrypted'],
            'reset_link_expires_at' => $context['expires_at']->toIso8601String(),
        ]));

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

    private function resetContext(Request $request, string $token, string $email): array
    {
        $email = strtolower(trim($email));
        $token = trim($token);

        if ($email === '' || $token === '') {
            return ['ok' => false, 'reason' => 'missing'];
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $this->canReceiveResetLink($user)) {
            return ['ok' => false, 'reason' => 'user'];
        }

        $table = (string) config('auth.passwords.users.table', 'password_reset_tokens');
        $record = DB::table($table)->where('email', $email)->first();

        if (! $record || empty($record->token) || empty($record->created_at)) {
            return ['ok' => false, 'reason' => 'record'];
        }

        $createdAt = Carbon::parse((string) $record->created_at);
        $expiresAt = $createdAt->copy()->addMinutes(max(1, (int) config('auth.passwords.users.expire', 60)));

        if ($expiresAt->isPast()) {
            return ['ok' => false, 'reason' => 'expired'];
        }

        $tokenMatches = false;

        try {
            $tokenMatches = Hash::check($token, (string) $record->token);
        } catch (Throwable) {
            $tokenMatches = hash_equals((string) $record->token, $token);
        }

        if (! $tokenMatches) {
            return ['ok' => false, 'reason' => 'token'];
        }

        return ['ok' => true, 'reason' => 'valid', 'user' => $user, 'email' => $email, 'expires_at' => $expiresAt];
    }

    private function pendingPasswordResetPayload(Request $request): ?array
    {
        $payload = $request->session()->get('auth.password_reset_otp');

        return is_array($payload) ? $payload : null;
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

    private function safeResetFailure(Request $request, string $message, int $status = 422)
    {
        $this->logPasswordEvent($request, 'password_reset_failed', 'medium', 'Password reset failed before OTP. message='.$message);

        if ($this->expectsJson($request)) {
            return response()->json(['ok' => false, 'message' => $message, 'errors' => ['email' => [$message]]], $status);
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => $message]);
    }

    private function safeOtpFailure(Request $request, string $message, int $status = 422, bool $forceRestart = false, array $extra = [])
    {
        if ($this->expectsJson($request)) {
            return response()->json(array_merge([
                'ok' => false,
                'message' => $message,
                'force_relogin' => $forceRestart,
                'redirect_url' => $forceRestart ? route('password.request') : null,
                'errors' => ['otp_code' => [$message]],
            ], $extra), $status);
        }

        if ($forceRestart) {
            return redirect()->route('password.request')->with('status', $message);
        }

        return back()->withErrors(['otp_code' => $message]);
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