<?php

namespace App\Models\Auth;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthOtpChallenge extends Model
{
    protected $fillable = [
        'user_id',
        'user_device_id',
        'challenge_token_hash',
        'purpose',
        'delivery_channel',
        'sent_to_masked',
        'otp_hash',
        'attempt_count',
        'max_attempts',
        'ip_address',
        'user_agent_hash',
        'device_fingerprint_hash',
        'expires_at',
        'verified_at',
        'consumed_at',
        'cancelled_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'consumed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}