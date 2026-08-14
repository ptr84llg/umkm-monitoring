<?php

namespace App\Models\Umkm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UmkmLegality extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'umkm_id',
        'nib_number',
        'oss_risk_level',
        'business_license_number',
        'pb_umku_number',
        'effective_date',
        'expired_date',
        'status_data',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expired_date' => 'date',
        'lss_detail_synced_at' => 'datetime',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }
}
