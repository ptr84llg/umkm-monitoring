<?php

namespace App\Models\Umkm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmCurrentProfileOverride extends Model
{
    protected $fillable = [
        'umkm_id',
        'override_revision_id',
        'updated_by_user_id',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(UmkmProfileOverrideRevision::class, 'override_revision_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}