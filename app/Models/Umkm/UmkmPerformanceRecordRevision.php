<?php

namespace App\Models\Umkm;

use App\Models\General\MonitoringPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class UmkmPerformanceRecordRevision extends Model
{
    protected $fillable = [
        'umkm_id',
        'monitoring_period_id',
        'previous_revision_id',
        'revision_no',
        'monthly_revenue',
        'worker_count',
        'production_volume',
        'status_data',
        'submitted_by_user_id',
        'submitted_at',
        'revision_reason',
    ];

    protected $casts = [
        'revision_no' => 'integer',
        'monthly_revenue' => 'decimal:2',
        'worker_count' => 'integer',
        'production_volume' => 'integer',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Periodic performance revisions are append-only and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new LogicException('Periodic performance revisions are append-only and cannot be deleted.');
        });
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(MonitoringPeriod::class, 'monitoring_period_id');
    }

    public function previousRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_revision_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }
}