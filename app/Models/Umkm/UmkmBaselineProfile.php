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
        'capital_amount' => 'decimal:2',
        'annual_sales_amount' => 'decimal:2',
        'baseline_monthly_revenue' => 'decimal:2',
        'loan_amount' => 'decimal:2',
        'lss_detail_synced_at' => 'datetime',
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
