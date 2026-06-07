<?php

namespace App\Models\Umkm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmDataQualityFlag extends Model
{
    protected $fillable = [
        'umkm_id',
        'flag_code',
        'flag_group',
        'severity',
        'description',
        'detected_value',
        'status',
        'source_type',
        'detected_at',
        'last_checked_at',
        'resolved_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }
}
