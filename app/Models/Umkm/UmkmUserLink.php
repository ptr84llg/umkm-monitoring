<?php

namespace App\Models\Umkm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class UmkmUserLink extends Model
{
    public const BINDING_SOURCE_ACCOUNT_CLAIM_ACTIVATION = 'account_claim_activation';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_REVOKED = 'revoked';

    protected $fillable = [
        'umkm_id',
        'user_id',
        'relationship_type',
        'is_primary',
        'source_claim_id',
        'binding_source',
        'verification_status',
        'is_active',
        'verified_at',
        'verified_by_user_id',
        'revoked_at',
        'revoked_by_user_id',
        'revocation_reason',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $link): void {
            foreach ([
                'umkm_id',
                'user_id',
                'relationship_type',
                'source_claim_id',
                'binding_source',
                'verified_at',
                'verified_by_user_id',
            ] as $column) {
                if ($link->isDirty($column)) {
                    throw new LogicException(
                        "Ownership binding provenance is immutable: {$column}."
                    );
                }
            }
        });

        static::deleting(function (): void {
            throw new LogicException(
                'Ownership binding cannot be deleted. Revoke it through an audited workflow.'
            );
        });
    }

    public function scopeActiveVerified(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('verification_status', self::VERIFICATION_VERIFIED)
            ->whereNotNull('source_claim_id')
            ->whereNotNull('verified_at')
            ->whereNull('revoked_at');
    }

    public function isActiveVerified(): bool
    {
        return (bool) $this->is_active
            && $this->verification_status === self::VERIFICATION_VERIFIED
            && $this->source_claim_id !== null
            && $this->verified_at !== null
            && $this->revoked_at === null;
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceClaim(): BelongsTo
    {
        return $this->belongsTo(UmkmAccountClaim::class, 'source_claim_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }
}