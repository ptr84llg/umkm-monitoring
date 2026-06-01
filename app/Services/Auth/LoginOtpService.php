<?php

namespace App\Services\Auth;

use App\Models\AuthOtpChallenge;
use App\Models\SecurityEventLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class LoginOtpService
{
    public const PURPOSE_MANUAL_LOGIN = 'manual_login';
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    private const OTP_EXPIRY_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function requiresOtpForManualLogin(User $user, Request $request): bool
    {
        foreach (['admin_utama', 'admin_dinas', 'kepala_dinas', 'validator_ahli'] as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function createLoginChallenge(User $user, Request $request, string $redirectUrl, bool $isResend = false): array
    {
        $challenge = $this->createChallenge($user, $request, self::PURPOSE_MANUAL_LOGIN, $isResend);
        $challenge['session_payload']['redirect_url'] = $redirectUrl;

        return $challenge;
    }

    public function createPasswordResetChallenge(User $user, Request $request, bool $isResend = false): array
    {
        return $this->createChallenge($user, $request, self::PURPOSE_PASSWORD_RESET, $isResend);
    }

    public function activeChallengeForPayload(?array $payload, ?Request $request = null, ?string $purpose = null): array
    {
        if (! is_array($payload)) {
            return ['ok' => false, 'challenge' => null, 'message' => 'Sesi verifikasi tidak tersedia. Silakan mulai ulang proses.'];
        }

        foreach (['challenge_id', 'challenge_token', 'user_id'] as $key) {
            if (empty($payload[$key])) {
                return ['ok' => false, 'challenge' => null, 'message' => 'Sesi verifikasi tidak lengkap. Silakan mulai ulang proses.'];
            }
        }

        $expectedPurpose = $purpose ?: (string) ($payload['purpose'] ?? self::PURPOSE_MANUAL_LOGIN);

        $challenge = AuthOtpChallenge::query()
            ->where('id', (int) $payload['challenge_id'])
            ->where('user_id', (int) $payload['user_id'])
            ->where('purpose', $expectedPurpose)
            ->where('challenge_token_hash', $this->tokenHash((string) $payload['challenge_token']))
            ->where('status', 'pending')
            ->whereNull('verified_at')
            ->whereNull('consumed_at')
            ->whereNull('cancelled_at')
            ->first();

        if (! $challenge) {
            return ['ok' => false, 'challenge' => null, 'message' => 'Sesi verifikasi tidak aktif. Silakan mulai ulang proses.'];
        }

        if ($challenge->expires_at->isPast()) {
            $challenge->forceFill(['status' => 'expired', 'cancelled_at' => now()])->save();

            $user = User::query()->find((int) $payload['user_id']);
            $this->logEvent($request, $user, 'otp_locked_or_expired', 'medium', $this->purposeLabel($expectedPurpose).' OTP challenge expired before page use. challenge_id='.$challenge->id);

            return ['ok' => false, 'challenge' => null, 'message' => 'Kode OTP sudah kedaluwarsa. Silakan mulai ulang proses untuk memulai verifikasi baru.'];
        }

        return ['ok' => true, 'challenge' => $challenge, 'message' => 'OTP aktif.'];
    }

    public function resendAvailableAtForPayload(?array $payload, ?AuthOtpChallenge $challenge = null): Carbon
    {
        if (is_array($payload) && ! empty($payload['resend_available_at'])) {
            try {
                return Carbon::parse((string) $payload['resend_available_at']);
            } catch (Throwable) {
                // Fallback below.
            }
        }

        return ($challenge?->created_at ?: now())->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS);
    }

    public function resendCooldownSecondsForPayload(?array $payload, ?AuthOtpChallenge $challenge = null): int
    {
        $resendAvailableAt = $this->resendAvailableAtForPayload($payload, $challenge);

        if ($resendAvailableAt->isPast()) {
            return 0;
        }

        return (int) ceil(max(0, now()->diffInSeconds($resendAvailableAt, false)));
    }

    public function verifyLoginChallenge(User $user, string $challengeToken, string $otp, Request $request): array
    {
        return $this->verifyChallenge($user, $challengeToken, $otp, $request, self::PURPOSE_MANUAL_LOGIN);
    }

    public function verifyPasswordResetChallenge(User $user, string $challengeToken, string $otp, Request $request): array
    {
        return $this->verifyChallenge($user, $challengeToken, $otp, $request, self::PURPOSE_PASSWORD_RESET);
    }

    private function createChallenge(User $user, Request $request, string $purpose, bool $isResend): array
    {
        $this->cancelPendingChallenges($user, $purpose);

        $otp = (string) random_int(100000, 999999);
        $challengeToken = Str::random(64);
        $fingerprintHash = $this->deviceFingerprintHash($request);
        $userAgentHash = $this->userAgentHash($request);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);
        $resendAvailableAt = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS);
        $maskedEmail = $this->maskEmail((string) $user->email);

        $challenge = AuthOtpChallenge::query()->create([
            'user_id' => $user->id,
            'user_device_id' => null,
            'challenge_token_hash' => $this->tokenHash($challengeToken),
            'purpose' => $purpose,
            'delivery_channel' => 'email',
            'sent_to_masked' => $maskedEmail,
            'otp_hash' => $this->otpHash($otp),
            'attempt_count' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'ip_address' => $request->ip(),
            'user_agent_hash' => $userAgentHash,
            'device_fingerprint_hash' => $fingerprintHash,
            'expires_at' => $expiresAt,
            'status' => 'pending',
        ]);

        $this->sendOtp($user, $otp, $expiresAt, $purpose);

        $this->logEvent(
            $request,
            $user,
            $isResend ? 'otp_resend_requested' : 'otp_challenge_created',
            'medium',
            $this->purposeLabel($purpose).' OTP '.($isResend ? 'resent.' : 'challenge created.').' challenge_id='.$challenge->id
        );

        return [
            'challenge' => $challenge,
            'session_payload' => [
                'challenge_id' => $challenge->id,
                'challenge_token' => $challengeToken,
                'user_id' => $user->id,
                'purpose' => $purpose,
                'masked_email' => $maskedEmail,
                'expires_at' => $expiresAt->toIso8601String(),
                'resend_available_at' => $resendAvailableAt->toIso8601String(),
            ],
        ];
    }

    private function verifyChallenge(User $user, string $challengeToken, string $otp, Request $request, string $purpose): array
    {
        $challenge = AuthOtpChallenge::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('challenge_token_hash', $this->tokenHash($challengeToken))
            ->where('status', 'pending')
            ->whereNull('verified_at')
            ->whereNull('consumed_at')
            ->whereNull('cancelled_at')
            ->latest('id')
            ->first();

        if (! $challenge) {
            $this->logEvent($request, $user, 'otp_verify_failed', 'medium', $this->purposeLabel($purpose).' OTP verification failed because challenge was not found.');

            return ['ok' => false, 'message' => 'Kode OTP belum dapat diverifikasi. Minta kode baru dan coba lagi.'];
        }

        if ($challenge->expires_at->isPast()) {
            $challenge->forceFill(['status' => 'expired', 'cancelled_at' => now()])->save();
            $this->logEvent($request, $user, 'otp_locked_or_expired', 'medium', $this->purposeLabel($purpose).' OTP challenge expired. challenge_id='.$challenge->id);

            return ['ok' => false, 'message' => 'Kode OTP sudah kedaluwarsa. Minta kode baru dan coba lagi.'];
        }

        if ((int) $challenge->attempt_count >= (int) $challenge->max_attempts) {
            $challenge->forceFill(['status' => 'locked', 'cancelled_at' => now()])->save();
            $this->logEvent($request, $user, 'otp_locked_or_expired', 'high', $this->purposeLabel($purpose).' OTP challenge locked because max attempts reached. challenge_id='.$challenge->id);

            return ['ok' => false, 'message' => 'Percobaan OTP sudah mencapai batas. Minta kode baru dan coba lagi.'];
        }

        if (! hash_equals((string) $challenge->otp_hash, $this->otpHash($otp))) {
            $attemptCount = (int) $challenge->attempt_count + 1;
            $nextStatus = $attemptCount >= (int) $challenge->max_attempts ? 'locked' : 'pending';

            $challenge->forceFill([
                'attempt_count' => $attemptCount,
                'status' => $nextStatus,
                'cancelled_at' => $nextStatus === 'locked' ? now() : null,
            ])->save();

            $this->logEvent($request, $user, 'otp_verify_failed', $nextStatus === 'locked' ? 'high' : 'medium', $this->purposeLabel($purpose).' OTP verification failed. challenge_id='.$challenge->id.' attempt_count='.$attemptCount);

            return [
                'ok' => false,
                'message' => $nextStatus === 'locked'
                    ? 'Percobaan OTP sudah mencapai batas. Minta kode baru dan coba lagi.'
                    : 'Kode OTP belum sesuai. Periksa kembali kode yang dikirim.',
            ];
        }

        $challenge->forceFill(['status' => 'consumed', 'verified_at' => now(), 'consumed_at' => now()])->save();
        $this->logEvent($request, $user, 'otp_verify_success', 'medium', $this->purposeLabel($purpose).' OTP verification succeeded. challenge_id='.$challenge->id);

        return ['ok' => true, 'message' => 'Verifikasi OTP berhasil.'];
    }

    private function cancelPendingChallenges(User $user, string $purpose): void
    {
        AuthOtpChallenge::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled', 'cancelled_at' => now(), 'updated_at' => now()]);
    }

    private function sendOtp(User $user, string $otp, $expiresAt, string $purpose): void
    {
        try {
            $purposeLabel = $purpose === self::PURPOSE_PASSWORD_RESET ? 'pengaturan ulang password' : 'login';
            $subject = $purpose === self::PURPOSE_PASSWORD_RESET
                ? 'Kode OTP Reset Password UMKM Monitoring'
                : 'Kode OTP Login UMKM Monitoring';

            Mail::raw(
                "Kode OTP {$purposeLabel} UMKM Monitoring Anda adalah: {$otp}\n\nKode berlaku sampai {$expiresAt->format('H:i')} dan tidak boleh dibagikan kepada pihak lain.",
                function ($message) use ($user, $subject): void {
                    $message->to((string) $user->email)->subject($subject);
                }
            );
        } catch (Throwable) {
            // Local development may use MAIL_MAILER=log. Delivery failure is intentionally not exposed.
        }
    }

    private function purposeLabel(string $purpose): string
    {
        return $purpose === self::PURPOSE_PASSWORD_RESET ? 'Password reset' : 'Login';
    }

    private function otpHash(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string) config('app.key'));
    }

    private function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function userAgentHash(Request $request): string
    {
        return hash('sha256', (string) $request->userAgent());
    }

    private function deviceFingerprintHash(Request $request): string
    {
        return hash('sha256', implode('|', [
            (string) $request->ip(),
            (string) $request->userAgent(),
            (string) $request->header('Accept-Language', ''),
        ]));
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return 'email akun';
        }

        [$name, $domain] = explode('@', $email, 2);
        $name = $name === '' ? 'u' : $name;

        return substr($name, 0, 1).str_repeat('*', max(2, strlen($name) - 1)).'@'.$domain;
    }

    private function logEvent(?Request $request, ?User $user, string $eventType, string $severity, string $detail): void
    {
        try {
            SecurityEventLog::query()->create([
                'actor_user_id' => $user?->id,
                'event_type' => $eventType,
                'severity' => $severity,
                'event_detail' => $detail,
                'ip_address' => $request?->ip(),
                'event_time' => now(),
            ]);
        } catch (Throwable) {
            // Security logging failure must not break the user-facing flow.
        }
    }
}