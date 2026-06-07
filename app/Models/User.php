<?php

namespace App\Models;

use App\Models\Access\Role;
use App\Models\Auth\AuthDeviceSession;
use App\Models\Auth\AuthOAuthIdentity;
use App\Models\Auth\UserDevice;
use App\Models\Auth\UserIdentityCredential;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const AUTH_PROVIDER_GOOGLE = 'google';

    protected $fillable = [
        'name',
        'email',
        'username',
        'email_verified_at',
        'password',
        'auth_provider_required',
        'manual_login_disabled_at',
        'google_linked_at',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'current_device_id',
        'current_device_fingerprint_hash',
        'last_login_user_agent_hash',
        'last_login_device_label',
        'last_login_browser_label',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'manual_login_disabled_at' => 'datetime',
            'google_linked_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function oauthIdentities(): HasMany
    {
        return $this->hasMany(AuthOAuthIdentity::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function currentDevice(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'current_device_id');
    }

    public function deviceSessions(): HasMany
    {
        return $this->hasMany(AuthDeviceSession::class);
    }

    public function identityCredentials(): HasMany
    {
        return $this->hasMany(UserIdentityCredential::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('code', $role)
            ->where('is_active', true)
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', fn ($query) => $query->where('code', $permission))
            ->exists();
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function requiresGoogleLogin(): bool
    {
        return $this->auth_provider_required === self::AUTH_PROVIDER_GOOGLE
            && $this->manual_login_disabled_at !== null;
    }

    public function manualLoginIsDisabled(): bool
    {
        return $this->manual_login_disabled_at !== null;
    }
}
