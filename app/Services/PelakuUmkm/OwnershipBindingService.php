<?php

namespace App\Services\PelakuUmkm;

use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmAccountClaim;
use App\Models\Umkm\UmkmUserLink;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class OwnershipBindingService
{
    private const RELATIONSHIP_TYPES = ['owner', 'manager', 'operator'];

    public function assertApplicantNotAlreadyBound(
        Umkm $umkm,
        string $email,
        string $relationshipType
    ): void {
        $this->assertRelationshipType($relationshipType);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$this->normalizeEmail($email)])
            ->first();

        if (! $user) {
            return;
        }

        $exists = UmkmUserLink::query()
            ->activeVerified()
            ->where('umkm_id', $umkm->id)
            ->where('user_id', $user->id)
            ->where('relationship_type', $relationshipType)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'claim' => 'Akun ini sudah memiliki binding kepemilikan aktif dan terverifikasi untuk UMKM tersebut.',
            ]);
        }
    }

    public function createFromActivatedClaim(
        UmkmAccountClaim $claim,
        User $user
    ): UmkmUserLink {
        if ($claim->status !== UmkmAccountClaim::STATUS_ACTIVATED
            || ! $claim->activation_completed_at
            || (int) $claim->activated_user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'binding' => 'Binding hanya dapat dibuat dari klaim yang telah menyelesaikan aktivasi.',
            ]);
        }

        if (! $claim->approved_at || ! $claim->reviewed_by_user_id) {
            throw ValidationException::withMessages([
                'binding' => 'Provenance approval Dinas tidak lengkap sehingga binding tidak dapat dibuat.',
            ]);
        }

        if (! $user->isActive() || ! $user->hasRole('pelaku_umkm')) {
            throw ValidationException::withMessages([
                'binding' => 'Akun Pelaku belum aktif atau role Pelaku UMKM belum valid.',
            ]);
        }

        if ($this->normalizeEmail((string) $user->email)
            !== $this->normalizeEmail((string) $claim->applicant_email)) {
            throw ValidationException::withMessages([
                'binding' => 'Identitas email akun tidak sesuai dengan klaim yang diaktivasi.',
            ]);
        }

        $relationshipType = (string) $claim->relationship_type;
        $this->assertRelationshipType($relationshipType);

        $byClaim = UmkmUserLink::query()
            ->where('source_claim_id', $claim->id)
            ->lockForUpdate()
            ->first();

        if ($byClaim) {
            if ((int) $byClaim->umkm_id === (int) $claim->umkm_id
                && (int) $byClaim->user_id === (int) $user->id
                && $byClaim->relationship_type === $relationshipType
                && $byClaim->isActiveVerified()) {
                return $byClaim;
            }

            throw ValidationException::withMessages([
                'binding' => 'Klaim ini sudah terhubung dengan binding lain dan memerlukan audit manual.',
            ]);
        }

        $existing = UmkmUserLink::query()
            ->where('umkm_id', $claim->umkm_id)
            ->where('user_id', $user->id)
            ->where('relationship_type', $relationshipType)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'binding' => 'Relasi UMKM dan akun ini sudah memiliki binding. Duplikasi provenance tidak diizinkan.',
            ]);
        }

        return UmkmUserLink::query()->create([
            'umkm_id' => $claim->umkm_id,
            'user_id' => $user->id,
            'relationship_type' => $relationshipType,
            'is_primary' => false,
            'source_claim_id' => $claim->id,
            'binding_source' => UmkmUserLink::BINDING_SOURCE_ACCOUNT_CLAIM_ACTIVATION,
            'verification_status' => UmkmUserLink::VERIFICATION_VERIFIED,
            'is_active' => true,
            'verified_at' => $claim->activation_completed_at,
            'verified_by_user_id' => $claim->reviewed_by_user_id,
            'revoked_at' => null,
            'revoked_by_user_id' => null,
            'revocation_reason' => null,
        ]);
    }

    private function assertRelationshipType(string $relationshipType): void
    {
        if (! in_array($relationshipType, self::RELATIONSHIP_TYPES, true)) {
            throw ValidationException::withMessages([
                'relationship_type' => 'Jenis hubungan akun dengan UMKM tidak valid.',
            ]);
        }
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}