<?php

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthDeviceSession extends Model
{
    protected $fillable = [
        'user_id',
        'user_device_id',
        'session_hash',
        'status',
        'login_method',
        'ip_address',
        'user_agent_hash',
        'user_agent',
        'browser_label',
        'device_fingerprint_hash',
        'requires_otp',
        'otp_verified_at',
        'activated_at',
        'last_seen_at',
        'revoked_at',
        'revoke_reason',
    ];

    protected $casts = [
        'requires_otp' => 'boolean',
        'otp_verified_at' => 'datetime',
        'activated_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'user_device_id');
    }
}
