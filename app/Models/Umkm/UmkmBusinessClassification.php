<?php

namespace App\Models\Umkm;

use App\Models\Reference\BusinessCategoryReference;
use App\Models\Reference\BusinessTypeReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmBusinessClassification extends Model
{
    protected $fillable = [
        'umkm_id',
        'business_category_id',
        'business_type_id',
        'is_primary',
        'status_data',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BusinessCategoryReference::class, 'business_category_id');
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessTypeReference::class, 'business_type_id');
    }
}
