<?php

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_fingerprint_hash',
        'user_agent_hash',
        'device_label',
        'browser_label',
        'platform_label',
        'ip_address',
        'is_trusted',
        'is_active',
        'trust_reason',
        'trusted_at',
        'first_seen_at',
        'last_seen_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'is_active' => 'boolean',
        'trusted_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AuthDeviceSession::class, 'user_device_id');
    }
}
