<?php

namespace App\Models\Reference;

use App\Models\Umkm\UmkmBusinessClassification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessTypeReference extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function classifications(): HasMany
    {
        return $this->hasMany(UmkmBusinessClassification::class, 'business_type_id');
    }
}
