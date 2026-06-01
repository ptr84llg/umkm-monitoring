<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthOAuthIdentity extends Model
{
    public const PROVIDER_GOOGLE = 'google';

    public const TYPE_INTERNAL_LINKED = 'internal_linked';

    public const TYPE_PUBLIC_LIMITED = 'public_limited';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REVOKED = 'revoked';

    protected $table = 'auth_oauth_identities';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'provider_email_hash',
        'provider_email_verified',
        'provider_name',
        'provider_avatar',
        'identity_type',
        'status',
        'linked_at',
        'cancelled_at',
        'revoked_at',
        'last_login_at',
        'last_login_ip',
        'last_user_agent_hash',
        'last_device_fingerprint_hash',
        'provider_payload_min',
    ];

    protected function casts(): array
    {
        return [
            'provider_email_verified' => 'boolean',
            'provider_payload_min' => 'array',
            'linked_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeGoogle(Builder $query): Builder
    {
        return $query->where('provider', self::PROVIDER_GOOGLE);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePublicLimited(Builder $query): Builder
    {
        return $query->where('identity_type', self::TYPE_PUBLIC_LIMITED);
    }

    public function scopeInternalLinked(Builder $query): Builder
    {
        return $query->where('identity_type', self::TYPE_INTERNAL_LINKED);
    }

    public function isPublicLimited(): bool
    {
        return $this->identity_type === self::TYPE_PUBLIC_LIMITED;
    }

    public function isInternalLinked(): bool
    {
        return $this->identity_type === self::TYPE_INTERNAL_LINKED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}