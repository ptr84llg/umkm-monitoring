<?php

namespace App\Services\Auth;

use App\Models\AuthOAuthIdentity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class OAuthIdentityService
{
    public function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    public function emailHash(?string $email): ?string
    {
        $email = $this->normalizeEmail($email);

        return $email ? hash('sha256', $email) : null;
    }

    public function userAgentHash(Request $request): ?string
    {
        $userAgent = (string) $request->userAgent();

        return $userAgent !== '' ? hash('sha256', $userAgent) : null;
    }

    public function findActiveGoogleIdentityByProviderId(string $providerId): ?AuthOAuthIdentity
    {
        return AuthOAuthIdentity::query()
            ->google()
            ->active()
            ->where('provider_id', $providerId)
            ->first();
    }

    public function findUserByVerifiedEmail(?string $email): ?User
    {
        $email = $this->normalizeEmail($email);

        if (! $email) {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    public function stageGooglePayload(array $googleUser, Request $request): array
    {
        $providerId = trim((string) ($googleUser['id'] ?? ''));

        if ($providerId === '') {
            throw new InvalidArgumentException('Google provider id tidak tersedia.');
        }

        $email = $this->normalizeEmail($googleUser['email'] ?? null);
        $emailVerified = (bool) ($googleUser['email_verified'] ?? false);

        return [
            'provider' => AuthOAuthIdentity::PROVIDER_GOOGLE,
            'provider_id' => $providerId,
            'provider_email' => $email,
            'provider_email_hash' => $this->emailHash($email),
            'provider_email_verified' => $emailVerified,
            'provider_name' => $this->safeString($googleUser['name'] ?? null, 191),
            'provider_avatar' => $this->safeString($googleUser['avatar'] ?? null, 2048),
            'last_login_ip' => $request->ip(),
            'last_user_agent_hash' => $this->userAgentHash($request),
            'provider_payload_min' => [
                'id_present' => true,
                'email_present' => $email !== null,
                'email_verified' => $emailVerified,
                'name_present' => ! empty($googleUser['name']),
                'avatar_present' => ! empty($googleUser['avatar']),
            ],
        ];
    }

    public function linkGoogleToInternalUser(User $user, array $stagedIdentity, Request $request): AuthOAuthIdentity
    {
        return DB::transaction(function () use ($user, $stagedIdentity, $request): AuthOAuthIdentity {
            $providerId = (string) $stagedIdentity['provider_id'];

            $existing = $this->findActiveGoogleIdentityByProviderId($providerId);

            if ($existing && (int) $existing->user_id !== (int) $user->id) {
                throw new RuntimeException('Identitas Google sudah tertaut ke akun lain.');
            }

            $identity = AuthOAuthIdentity::query()->updateOrCreate(
                [
                    'provider' => AuthOAuthIdentity::PROVIDER_GOOGLE,
                    'provider_id' => $providerId,
                ],
                array_merge($stagedIdentity, [
                    'user_id' => $user->id,
                    'identity_type' => AuthOAuthIdentity::TYPE_INTERNAL_LINKED,
                    'status' => AuthOAuthIdentity::STATUS_ACTIVE,
                    'linked_at' => now(),
                    'cancelled_at' => null,
                    'revoked_at' => null,
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                    'last_user_agent_hash' => $this->userAgentHash($request),
                ])
            );

            $user->forceFill([
                'auth_provider_required' => User::AUTH_PROVIDER_GOOGLE,
                'manual_login_disabled_at' => now(),
                'google_linked_at' => now(),
            ])->save();

            return $identity;
        });
    }

    public function storePublicLimitedGoogleIdentity(array $stagedIdentity, Request $request): AuthOAuthIdentity
    {
        return DB::transaction(function () use ($stagedIdentity, $request): AuthOAuthIdentity {
            return AuthOAuthIdentity::query()->updateOrCreate(
                [
                    'provider' => AuthOAuthIdentity::PROVIDER_GOOGLE,
                    'provider_id' => (string) $stagedIdentity['provider_id'],
                ],
                array_merge($stagedIdentity, [
                    'user_id' => null,
                    'identity_type' => AuthOAuthIdentity::TYPE_PUBLIC_LIMITED,
                    'status' => AuthOAuthIdentity::STATUS_ACTIVE,
                    'linked_at' => null,
                    'cancelled_at' => null,
                    'revoked_at' => null,
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                    'last_user_agent_hash' => $this->userAgentHash($request),
                ])
            );
        });
    }

    public function cancelPendingSession(Request $request, string $sessionKey = 'auth.google.pending'): void
    {
        $request->session()->forget($sessionKey);
    }

    private function safeString(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Str::limit($value, $maxLength, '');
    }
}