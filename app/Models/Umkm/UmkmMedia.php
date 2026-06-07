<?php

namespace App\Models\Umkm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UmkmMedia extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'umkm_id',
        'media_type',
        'media_role',
        'source_path',
        'source_url',
        'local_path',
        'source_hash',
        'caption',
        'sort_order',
        'is_primary',
        'visibility',
        'status_data',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }
}
