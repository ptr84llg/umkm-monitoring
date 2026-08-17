<?php

namespace App\Services\PelakuUmkm;

use App\Models\Access\Role;
use App\Models\Auth\UserIdentityCredential;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmAccountClaim;
use App\Models\Umkm\UmkmAccountClaimEvent;
use App\Models\Umkm\UmkmClaimActivationChallenge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountClaimActivationService
{
    private const ACTIVATION_TTL_MINUTES = 10;
    private const MAX_OTP_ATTEMPTS = 5;

    public function __construct(
        private readonly OwnershipBindingService $ownershipBindings
    ) {
    }

    public function submitSelfClaim(array $data, Request $request): UmkmAccountClaim
    {
        $umkm = $this->resolveUmkm((string) $data['umkm_code']);
        $email = $this->normalizeEmail((string) $data['applicant_email']);

        $this->ownershipBindings->assertApplicantNotAlreadyBound(
            $umkm,
            $email,
            'owner'
        );

        return DB::transaction(function () use ($data, $request, $umkm, $email): UmkmAccountClaim {
            $this->guardNoOpenClaim($umkm->id, $email);

            $rejected = UmkmAccountClaim::query()
                ->where('umkm_id', $umkm->id)
                ->whereRaw('LOWER(applicant_email) = ?', [$email])
                ->where('status', UmkmAccountClaim::STATUS_REJECTED)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $claim = UmkmAccountClaim::query()->create([
                'umkm_id' => $umkm->id,
                'claim_reference' => (string) Str::ulid(),
                'claim_type' => UmkmAccountClaim::TYPE_SELF_CLAIM,
                'applicant_name' => trim((string) $data['applicant_name']),
                'applicant_email' => $email,
                'relationship_type' => 'owner',
                'status' => UmkmAccountClaim::STATUS_PENDING_REVIEW,
                'resubmission_of_id' => $rejected?->id,
                'submitted_at' => now(),
            ]);

            $this->recordEvent(
                $claim,
                $rejected ? 'claim_resubmitted' : 'claim_submitted',
                null,
                $claim->status,
                null,
                $request,
                [
                    'claim_type' => $claim->claim_type,
                    'relationship_type' => 'owner',
                    'resubmission_of_id' => $rejected?->id,
                ]
            );

            return $claim;
        });
    }

    public function createDinasInvite(User $actor, array $data, Request $request): array
    {
        $umkm = $this->resolveUmkm((string) $data['umkm_code']);
        $email = $this->normalizeEmail((string) $data['applicant_email']);

        $this->ownershipBindings->assertApplicantNotAlreadyBound(
            $umkm,
            $email,
            'owner'
        );

        $claim = DB::transaction(function () use ($actor, $data, $request, $umkm, $email): UmkmAccountClaim {
            $this->guardNoOpenClaim($umkm->id, $email);

            $claim = UmkmAccountClaim::query()->create([
                'umkm_id' => $umkm->id,
                'claim_reference' => (string) Str::ulid(),
                'claim_type' => UmkmAccountClaim::TYPE_DINAS_INVITE,
                'applicant_name' => trim((string) $data['applicant_name']),
                'applicant_email' => $email,
                'relationship_type' => 'owner',
                'status' => UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION,
                'submitted_by_user_id' => $actor->id,
                'reviewed_by_user_id' => $actor->id,
                'review_note' => trim((string) ($data['review_note'] ?? '')) ?: null,
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'approved_at' => now(),
            ]);

            $this->recordEvent(
                $claim,
                'dinas_invitation_approved',
                null,
                $claim->status,
                $actor,
                $request,
                ['relationship_type' => 'owner']
            );

            return $claim;
        });

        return [
            'claim' => $claim,
            'delivery_ok' => $this->issueActivationChallenge($claim, $actor, $request),
        ];
    }

    public function review(User $actor, UmkmAccountClaim $claim, string $action, ?string $reviewNote, Request $request): array
    {
        $reviewed = DB::transaction(function () use ($actor, $claim, $action, $reviewNote, $request): UmkmAccountClaim {
            $locked = UmkmAccountClaim::query()->lockForUpdate()->findOrFail($claim->id);

            if ($locked->status !== UmkmAccountClaim::STATUS_PENDING_REVIEW) {
                throw ValidationException::withMessages([
                    'action' => 'Klaim ini tidak lagi berada pada status menunggu review.',
                ]);
            }

            $from = $locked->status;
            $now = now();

            if ($action === 'approve') {
                $locked->forceFill([
                    'status' => UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION,
                    'reviewed_by_user_id' => $actor->id,
                    'review_note' => $reviewNote,
                    'reviewed_at' => $now,
                    'approved_at' => $now,
                    'rejected_at' => null,
                ])->save();

                $event = 'claim_approved';
            } elseif ($action === 'reject') {
                $locked->forceFill([
                    'status' => UmkmAccountClaim::STATUS_REJECTED,
                    'reviewed_by_user_id' => $actor->id,
                    'review_note' => $reviewNote,
                    'reviewed_at' => $now,
                    'rejected_at' => $now,
                    'approved_at' => null,
                ])->save();

                $event = 'claim_rejected';
            } else {
                throw ValidationException::withMessages([
                    'action' => 'Tindakan review tidak valid.',
                ]);
            }

            $this->recordEvent(
                $locked,
                $event,
                $from,
                $locked->status,
                $actor,
                $request,
                ['review_note_present' => trim((string) $reviewNote) !== '']
            );

            return $locked;
        });

        $deliveryOk = true;

        if ($reviewed->status === UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION) {
            $deliveryOk = $this->issueActivationChallenge($reviewed, $actor, $request);
        }

        return [
            'claim' => $reviewed,
            'delivery_ok' => $deliveryOk,
        ];
    }

    public function resendActivation(User $actor, UmkmAccountClaim $claim, Request $request): bool
    {
        $claim->refresh();

        if ($claim->status !== UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION) {
            throw ValidationException::withMessages([
                'activation' => 'Aktivasi hanya dapat dikirim ulang untuk klaim yang telah disetujui dan belum diaktivasi.',
            ]);
        }

        return $this->issueActivationChallenge($claim, $actor, $request);
    }

    public function activationPageData(UmkmAccountClaim $claim, string $rawToken): ?array
    {
        if ($claim->status !== UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION) {
            return null;
        }

        $challenge = UmkmClaimActivationChallenge::query()
            ->where('claim_id', $claim->id)
            ->where('challenge_token_hash', hash('sha256', $rawToken))
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $challenge) {
            return null;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$this->normalizeEmail($claim->applicant_email)])
            ->first();

        if ($user && $user->isActive()) {
            $roles = $this->activeRoleCodes($user);

            if ($roles !== ['pelaku_umkm']) {
                return null;
            }
        }

        return [
            'masked_email' => $this->maskEmail($claim->applicant_email),
            'requires_password' => ! $user || ! $user->isActive(),
            'expires_at' => $challenge->expires_at,
        ];
    }

    public function activate(UmkmAccountClaim $claim, array $data, Request $request): UmkmAccountClaim
    {
        $rawToken = (string) $data['activation_token'];
        $otp = (string) $data['otp'];

        return DB::transaction(function () use ($claim, $data, $request, $rawToken, $otp): UmkmAccountClaim {
            $lockedClaim = UmkmAccountClaim::query()->lockForUpdate()->findOrFail($claim->id);

            if ($lockedClaim->status !== UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION) {
                throw ValidationException::withMessages([
                    'otp' => 'Klaim tidak berada pada status yang dapat diaktivasi.',
                ]);
            }

            $challenge = UmkmClaimActivationChallenge::query()
                ->where('claim_id', $lockedClaim->id)
                ->where('challenge_token_hash', hash('sha256', $rawToken))
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $challenge || $challenge->status !== 'pending') {
                throw ValidationException::withMessages([
                    'otp' => 'Token aktivasi tidak valid atau sudah tidak aktif.',
                ]);
            }

            if ($challenge->expires_at->isPast()) {
                $challenge->forceFill(['status' => 'expired'])->save();

                $this->recordEvent(
                    $lockedClaim,
                    'activation_challenge_expired',
                    $lockedClaim->status,
                    $lockedClaim->status,
                    null,
                    $request
                );

                throw ValidationException::withMessages([
                    'otp' => 'Masa berlaku aktivasi telah berakhir. Hubungi Dinas untuk pengiriman ulang.',
                ]);
            }

            $expected = $this->otpHash($lockedClaim->claim_reference, $otp);

            if (! hash_equals((string) $challenge->otp_hash, $expected)) {
                $attempts = (int) $challenge->attempt_count + 1;
                $locked = $attempts >= (int) $challenge->max_attempts;

                $challenge->forceFill([
                    'attempt_count' => $attempts,
                    'status' => $locked ? 'locked' : 'pending',
                ])->save();

                $this->recordEvent(
                    $lockedClaim,
                    $locked ? 'activation_otp_locked' : 'activation_otp_failed',
                    $lockedClaim->status,
                    $lockedClaim->status,
                    null,
                    $request,
                    ['attempt_count' => $attempts]
                );

                throw ValidationException::withMessages([
                    'otp' => $locked
                        ? 'Batas percobaan OTP telah tercapai. Hubungi Dinas untuk pengiriman ulang aktivasi.'
                        : 'Kode OTP tidak sesuai.',
                ]);
            }

            $user = $this->resolveOrCreatePelakuUser($lockedClaim, $data, $request);
            $role = Role::query()
                ->where('code', 'pelaku_umkm')
                ->where('is_active', true)
                ->first();

            if (! $role) {
                throw ValidationException::withMessages([
                    'account' => 'Role Pelaku UMKM aktif tidak tersedia.',
                ]);
            }

            $user->roles()->syncWithoutDetaching([$role->id]);

            $normalizedEmail = $this->normalizeEmail($lockedClaim->applicant_email);

            UserIdentityCredential::query()->updateOrCreate(
                [
                    'identifier_type' => 'email',
                    'identifier_hash' => $this->identifierHash($normalizedEmail),
                ],
                [
                    'user_id' => $user->id,
                    'identifier_masked' => $this->maskEmail($normalizedEmail),
                    'is_active' => true,
                    'login_enabled' => true,
                    'verified_at' => now(),
                    'login_enabled_at' => now(),
                    'login_enabled_by' => null,
                ]
            );

            $activationCompletedAt = now();

            $challenge->forceFill([
                'verified_at' => $activationCompletedAt,
                'consumed_at' => $activationCompletedAt,
                'status' => 'consumed',
            ])->save();

            $from = $lockedClaim->status;

            $lockedClaim->forceFill([
                'status' => UmkmAccountClaim::STATUS_ACTIVATED,
                'activated_user_id' => $user->id,
                'activation_completed_at' => $activationCompletedAt,
            ])->save();

            $binding = $this->ownershipBindings->createFromActivatedClaim(
                $lockedClaim,
                $user
            );

            $this->recordEvent(
                $lockedClaim,
                'account_activation_completed',
                $from,
                $lockedClaim->status,
                $user,
                $request,
                [
                    'credential_verified' => true,
                    'ownership_binding_created' => true,
                    'ownership_binding_id' => $binding->id,
                    'ownership_binding_source' => $binding->binding_source,
                ]
            );

            return $lockedClaim->refresh();
        });
    }

    private function resolveOrCreatePelakuUser(UmkmAccountClaim $claim, array $data, Request $request): User
    {
        $email = $this->normalizeEmail($claim->applicant_email);
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->lockForUpdate()
            ->first();

        if ($user && $user->isActive()) {
            if ($this->activeRoleCodes($user) !== ['pelaku_umkm']) {
                throw ValidationException::withMessages([
                    'account' => 'Email ini terhubung dengan akun aktif yang tidak dapat dipromosikan melalui proses klaim.',
                ]);
            }

            return $user;
        }

        $password = (string) ($data['password'] ?? '');

        if (mb_strlen($password) < 12) {
            throw ValidationException::withMessages([
                'password' => 'Password wajib dibuat sendiri dan minimal terdiri dari 12 karakter.',
            ]);
        }

        if ($user) {
            $roles = $this->activeRoleCodes($user);

            if (array_diff($roles, ['pelaku_umkm']) !== []) {
                throw ValidationException::withMessages([
                    'account' => 'Akun tidak aktif ini memiliki role lain dan tidak dapat diaktivasi melalui proses klaim Pelaku.',
                ]);
            }

            if ($user->manualLoginIsDisabled()) {
                throw ValidationException::withMessages([
                    'account' => 'Akun ini memiliki kebijakan provider login khusus dan memerlukan penanganan terpisah.',
                ]);
            }

            $user->forceFill([
                'name' => $claim->applicant_name,
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ])->save();

            return $user;
        }

        return User::query()->create([
            'name' => $claim->applicant_name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    private function issueActivationChallenge(UmkmAccountClaim $claim, ?User $actor, Request $request): bool
    {
        $rawToken = Str::random(64);
        $otp = (string) random_int(100000, 999999);

        $challenge = DB::transaction(function () use ($claim, $actor, $request, $rawToken, $otp): UmkmClaimActivationChallenge {
            UmkmClaimActivationChallenge::query()
                ->where('claim_id', $claim->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'updated_at' => now(),
                ]);

            $challenge = UmkmClaimActivationChallenge::query()->create([
                'claim_id' => $claim->id,
                'challenge_token_hash' => hash('sha256', $rawToken),
                'otp_hash' => $this->otpHash($claim->claim_reference, $otp),
                'delivery_channel' => 'email',
                'sent_to_masked' => $this->maskEmail($claim->applicant_email),
                'attempt_count' => 0,
                'max_attempts' => self::MAX_OTP_ATTEMPTS,
                'ip_address' => $request->ip(),
                'user_agent_hash' => $this->userAgentHash($request),
                'expires_at' => now()->addMinutes(self::ACTIVATION_TTL_MINUTES),
                'status' => 'pending',
            ]);

            $this->recordEvent(
                $claim,
                'activation_challenge_issued',
                $claim->status,
                $claim->status,
                $actor,
                $request,
                [
                    'delivery_channel' => 'email',
                    'sent_to_masked' => $challenge->sent_to_masked,
                    'expires_in_minutes' => self::ACTIVATION_TTL_MINUTES,
                ]
            );

            return $challenge;
        });

        $url = route('pelaku-activation.show', [
            'claim_reference' => $claim->claim_reference,
            'token' => $rawToken,
        ]);

        $body = implode(PHP_EOL, [
            'Aktivasi Akun Pelaku UMKM SISFODA',
            '',
            'Permohonan atau undangan aktivasi Anda telah disetujui oleh Dinas.',
            'Kode OTP: '.$otp,
            'Tautan aktivasi: '.$url,
            'OTP berlaku selama '.self::ACTIVATION_TTL_MINUTES.' menit.',
            '',
            'Dinas tidak membuat dan tidak mengetahui password Anda.',
            'Jika akun ini merupakan akun baru, buat password Anda sendiri pada halaman aktivasi.',
        ]);

        try {
            Mail::raw($body, function ($message) use ($claim): void {
                $message->to($claim->applicant_email)
                    ->subject('Aktivasi Akun Pelaku UMKM SISFODA');
            });

            return true;
        } catch (Throwable $exception) {
            DB::transaction(function () use ($challenge, $claim, $actor, $request, $exception): void {
                $challenge->refresh();
                $challenge->forceFill([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ])->save();

                $this->recordEvent(
                    $claim,
                    'activation_delivery_failed',
                    $claim->status,
                    $claim->status,
                    $actor,
                    $request,
                    ['error_class' => $exception::class]
                );
            });

            return false;
        }
    }

    private function guardNoOpenClaim(int $umkmId, string $email): void
    {
        $exists = UmkmAccountClaim::query()
            ->where('umkm_id', $umkmId)
            ->whereRaw('LOWER(applicant_email) = ?', [$email])
            ->whereIn('status', [
                UmkmAccountClaim::STATUS_PENDING_REVIEW,
                UmkmAccountClaim::STATUS_APPROVED_PENDING_ACTIVATION,
                UmkmAccountClaim::STATUS_ACTIVATED,
            ])
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'applicant_email' => 'Klaim aktif atau klaim yang telah selesai untuk email dan UMKM ini sudah tersedia.',
            ]);
        }
    }

    private function resolveUmkm(string $umkmCode): Umkm
    {
        $umkm = Umkm::query()
            ->where('umkm_code', trim($umkmCode))
            ->first();

        if (! $umkm) {
            throw ValidationException::withMessages([
                'umkm_code' => 'Kode UMKM tidak ditemukan.',
            ]);
        }

        return $umkm;
    }

    private function recordEvent(
        UmkmAccountClaim $claim,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?User $actor,
        Request $request,
        array $detail = []
    ): void {
        UmkmAccountClaimEvent::query()->create([
            'claim_id' => $claim->id,
            'actor_user_id' => $actor?->id,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'event_detail' => $detail === [] ? null : $detail,
            'ip_address' => $request->ip(),
            'user_agent_hash' => $this->userAgentHash($request),
            'event_time' => now(),
            'created_at' => now(),
        ]);
    }

    private function activeRoleCodes(User $user): array
    {
        $codes = $user->roles()
            ->where('roles.is_active', true)
            ->orderBy('code')
            ->pluck('code')
            ->values()
            ->all();

        return array_values($codes);
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function identifierHash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function otpHash(string $claimReference, string $otp): string
    {
        return hash_hmac('sha256', $claimReference.'|'.$otp, (string) config('app.key'));
    }

    private function userAgentHash(Request $request): ?string
    {
        $agent = trim((string) $request->userAgent());

        return $agent === '' ? null : hash('sha256', $agent);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($local === '' || $domain === '') {
            return '***';
        }

        $first = mb_substr($local, 0, 1);
        $visible = mb_strlen($local) > 2 ? $first.'***' : $first.'**';

        return $visible.'@'.$domain;
    }
}