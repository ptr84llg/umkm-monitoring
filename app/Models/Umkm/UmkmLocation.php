<?php

namespace App\Models\Umkm;

use App\Models\Reference\Region;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UmkmLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'umkm_id',
        'province_region_id',
        'city_region_id',
        'district_region_id',
        'village_region_id',
        'address_detail',
        'latitude',
        'longitude',
        'coordinate_status',
        'status_data',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'province_region_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'city_region_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'district_region_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'village_region_id');
    }
}
