<?php

namespace App\Models\Umkm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class UmkmClaimActivationChallenge extends Model
{
    protected $fillable = [
        'claim_id',
        'challenge_token_hash',
        'otp_hash',
        'delivery_channel',
        'sent_to_masked',
        'attempt_count',
        'max_attempts',
        'ip_address',
        'user_agent_hash',
        'expires_at',
        'verified_at',
        'consumed_at',
        'cancelled_at',
        'status',
    ];

    protected $hidden = [
        'challenge_token_hash',
        'otp_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'consumed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Activation challenge audit records cannot be deleted.');
        });
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(UmkmAccountClaim::class, 'claim_id');
    }
}