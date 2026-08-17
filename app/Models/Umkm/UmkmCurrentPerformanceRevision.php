<?php

namespace App\Models\Umkm;

use App\Models\General\MonitoringPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmCurrentPerformanceRevision extends Model
{
    protected $fillable = [
        'umkm_id',
        'monitoring_period_id',
        'performance_revision_id',
        'updated_by_user_id',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(MonitoringPeriod::class, 'monitoring_period_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(UmkmPerformanceRecordRevision::class, 'performance_revision_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}