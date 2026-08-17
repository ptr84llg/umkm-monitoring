<?php

namespace App\Models\Umkm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class UmkmAccountClaimEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'claim_id',
        'actor_user_id',
        'event_type',
        'from_status',
        'to_status',
        'event_detail',
        'ip_address',
        'user_agent_hash',
        'event_time',
        'created_at',
    ];

    protected $casts = [
        'event_detail' => 'array',
        'event_time' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Account claim event history is immutable.');
        });

        static::deleting(function (): void {
            throw new LogicException('Account claim event history is immutable.');
        });
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(UmkmAccountClaim::class, 'claim_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}