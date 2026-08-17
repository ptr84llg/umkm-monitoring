<?php

namespace App\Services\Auth;

use App\Models\Auth\AuthDeviceSession;
use App\Models\Auth\UserDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;

class SingleDeviceSessionService
{
    public const SESSION_ID_KEY = 'auth.device_session_id';
    public const SESSION_FINGERPRINT_KEY = 'auth.device_fingerprint_hash';
    public const SESSION_UUID_KEY = 'auth.device_uuid';
    public const DEVICE_COOKIE = 'umkm_device_id';

    public function activate(
        User $user,
        Request $request,
        string $loginMethod,
        bool $requiresOtp = false,
        bool $otpVerified = false
    ): AuthDeviceSession {
        $this->assertSchema();

        if (! $user->isActive()) {
            throw new LogicException('Inactive users cannot activate a device session.');
        }

        $deviceUuid = $this->resolveDeviceUuid($request);
        $fingerprintHash = hash('sha256', $deviceUuid);
        $userAgent = (string) $request->userAgent();
        $userAgentHash = hash('sha256', $userAgent);
        $sessionHash = $this->sessionHash($request);
        $now = now();

        $deviceSession = DB::transaction(function () use (
            $user,
            $request,
            $loginMethod,
            $requiresOtp,
            $otpVerified,
            $fingerprintHash,
            $userAgent,
            $userAgentHash,
            $sessionHash,
            $now
        ): AuthDeviceSession {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            AuthDeviceSession::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'replaced',
                    'revoked_at' => $now,
                    'revoke_reason' => 'single_device_replaced',
                    'updated_at' => $now,
                ]);

            UserDevice::query()
                ->where('user_id', $lockedUser->id)
                ->where('device_fingerprint_hash', '!=', $fingerprintHash)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'revoked_at' => $now,
                    'updated_at' => $now,
                ]);

            $device = UserDevice::query()->firstOrNew([
                'user_id' => $lockedUser->id,
                'device_fingerprint_hash' => $fingerprintHash,
            ]);

            if (! $device->exists) {
                $device->first_seen_at = $now;
            }

            $device->forceFill([
                'user_agent_hash' => $userAgentHash,
                'device_label' => 'Perangkat browser',
                'browser_label' => $this->browserLabel($userAgent),
                'platform_label' => $this->platformLabel($userAgent),
                'ip_address' => $request->ip(),
                'is_active' => true,
                'last_seen_at' => $now,
                'revoked_at' => null,
            ])->save();

            $session = AuthDeviceSession::query()->create([
                'user_id' => $lockedUser->id,
                'user_device_id' => $device->id,
                'session_hash' => $sessionHash,
                'status' => 'active',
                'login_method' => substr($loginMethod, 0, 32),
                'ip_address' => $request->ip(),
                'user_agent_hash' => $userAgentHash,
                'user_agent' => $userAgent,
                'browser_label' => $device->browser_label,
                'device_fingerprint_hash' => $fingerprintHash,
                'requires_otp' => $requiresOtp,
                'otp_verified_at' => $otpVerified ? $now : null,
                'activated_at' => $now,
                'last_seen_at' => $now,
            ]);

            $lockedUser->forceFill([
                'current_device_id' => $device->id,
                'current_device_fingerprint_hash' => $fingerprintHash,
                'last_login_user_agent_hash' => $userAgentHash,
                'last_login_device_label' => $device->device_label,
                'last_login_browser_label' => $device->browser_label,
            ])->save();

            return $session;
        });

        $request->session()->put(self::SESSION_ID_KEY, $deviceSession->id);
        $request->session()->put(self::SESSION_FINGERPRINT_KEY, $fingerprintHash);
        $request->session()->put(self::SESSION_UUID_KEY, $deviceUuid);

        Cookie::queue(Cookie::make(
            self::DEVICE_COOKIE,
            $deviceUuid,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        ));

        $user->refresh();

        return $deviceSession;
    }

    public function revokeCurrentSession(User $user, Request $request, string $reason = 'logout'): void
    {
        $this->assertSchema();
        $deviceSessionId = (int) $request->session()->get(self::SESSION_ID_KEY, 0);
        $now = now();

        DB::transaction(function () use ($user, $deviceSessionId, $reason, $now): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);
            if (! $lockedUser) {
                return;
            }

            if ($deviceSessionId > 0) {
                AuthDeviceSession::query()
                    ->where('id', $deviceSessionId)
                    ->where('user_id', $lockedUser->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'revoked',
                        'revoked_at' => $now,
                        'revoke_reason' => substr($reason, 0, 120),
                        'updated_at' => $now,
                    ]);
            }

            if ($lockedUser->current_device_id) {
                UserDevice::query()
                    ->where('id', $lockedUser->current_device_id)
                    ->where('user_id', $lockedUser->id)
                    ->update([
                        'is_active' => false,
                        'revoked_at' => $now,
                        'updated_at' => $now,
                    ]);
            }

            $lockedUser->forceFill([
                'current_device_id' => null,
                'current_device_fingerprint_hash' => null,
                'last_login_device_label' => null,
                'last_login_browser_label' => null,
            ])->save();
        });

        $request->session()->forget([
            self::SESSION_ID_KEY,
            self::SESSION_FINGERPRINT_KEY,
            self::SESSION_UUID_KEY,
        ]);
    }

    public function revokeAllForUser(User $user, string $reason): void
    {
        $this->assertSchema();
        $now = now();

        DB::transaction(function () use ($user, $reason, $now): void {
            $lockedUser = User::query()->lockForUpdate()->find($user->id);
            if (! $lockedUser) {
                return;
            }

            AuthDeviceSession::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => $now,
                    'revoke_reason' => substr($reason, 0, 120),
                    'updated_at' => $now,
                ]);

            UserDevice::query()
                ->where('user_id', $lockedUser->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'revoked_at' => $now,
                    'updated_at' => $now,
                ]);

            $lockedUser->forceFill([
                'current_device_id' => null,
                'current_device_fingerprint_hash' => null,
                'last_login_device_label' => null,
                'last_login_browser_label' => null,
            ])->save();
        });
    }

    public function sessionIsCurrent(User $user, Request $request): bool
    {
        $this->assertSchema();

        if (! $user->current_device_id) {
            return true;
        }

        $deviceSessionId = (int) $request->session()->get(self::SESSION_ID_KEY, 0);
        $sessionFingerprint = (string) $request->session()->get(self::SESSION_FINGERPRINT_KEY, '');

        if ($deviceSessionId <= 0 || $sessionFingerprint === '') {
            return false;
        }

        $session = AuthDeviceSession::query()
            ->where('id', $deviceSessionId)
            ->where('user_id', $user->id)
            ->where('user_device_id', $user->current_device_id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->first();

        if (! $session) {
            return false;
        }

        if (! hash_equals((string) $session->session_hash, $this->sessionHash($request))) {
            return false;
        }

        if (! hash_equals((string) $user->current_device_fingerprint_hash, $sessionFingerprint)) {
            return false;
        }

        if (! hash_equals((string) $session->device_fingerprint_hash, $sessionFingerprint)) {
            return false;
        }

        if ($session->last_seen_at === null || $session->last_seen_at->lt(now()->subMinutes(5))) {
            $session->forceFill(['last_seen_at' => now()])->save();
            UserDevice::query()->where('id', $session->user_device_id)->update([
                'last_seen_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return true;
    }

    private function resolveDeviceUuid(Request $request): string
    {
        $candidates = [
            $request->header('X-UMKM-Device-Id'),
            $request->cookie(self::DEVICE_COOKIE),
            $request->session()->get(self::SESSION_UUID_KEY),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if (preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $candidate)) {
                return $candidate;
            }
        }

        return (string) Str::uuid();
    }

    private function sessionHash(Request $request): string
    {
        $sessionId = (string) $request->session()->getId();
        if ($sessionId === '') {
            throw new LogicException('A started session is required for single-device enforcement.');
        }

        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }

    private function browserLabel(string $userAgent): string
    {
        foreach (['Edg/' => 'Edge', 'Chrome/' => 'Chrome', 'Firefox/' => 'Firefox', 'Safari/' => 'Safari'] as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                return $label;
            }
        }

        return 'Browser';
    }

    private function platformLabel(string $userAgent): string
    {
        foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'Macintosh' => 'macOS', 'Linux' => 'Linux'] as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                return $label;
            }
        }

        return 'Unknown';
    }

    private function assertSchema(): void
    {
        foreach (['users', 'user_devices', 'auth_device_sessions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new LogicException("Single-device enforcement requires table: {$table}");
            }
        }

        foreach (['current_device_id', 'current_device_fingerprint_hash'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                throw new LogicException("Single-device enforcement requires users.{$column}");
            }
        }
    }
}