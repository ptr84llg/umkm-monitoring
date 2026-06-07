<?php

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIdentityCredential extends Model
{
    protected $fillable = [
        'user_id',
        'identifier_type',
        'identifier_hash',
        'identifier_masked',
        'is_active',
        'login_enabled',
        'verified_at',
        'login_enabled_at',
        'login_enabled_by',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'login_enabled' => 'boolean',
        'verified_at' => 'datetime',
        'login_enabled_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loginEnabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'login_enabled_by');
    }
}
