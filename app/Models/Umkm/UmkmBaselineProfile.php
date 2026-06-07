<?php

namespace App\Models\Umkm;

use App\Models\Reference\MarketingMethodReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmBaselineProfile extends Model
{
    protected $fillable = [
        'umkm_id',
        'employee_count',
        'marketing_method_id',
        'status_data',
    ];

    protected $casts = [
        'employee_count' => 'integer',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function marketingMethod(): BelongsTo
    {
        return $this->belongsTo(MarketingMethodReference::class, 'marketing_method_id');
    }
}
