<?php

namespace App\Models\Reference;

use App\Models\Umkm\UmkmBaselineProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingMethodReference extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function baselineProfiles(): HasMany
    {
        return $this->hasMany(UmkmBaselineProfile::class, 'marketing_method_id');
    }
}
