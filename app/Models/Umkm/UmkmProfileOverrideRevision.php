<?php

namespace App\Models\Umkm;

use App\Models\User;
use App\Models\Validation\DataValidationReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class UmkmProfileOverrideRevision extends Model
{
    protected $fillable = [
        'umkm_id',
        'source_submission_id',
        'approved_review_id',
        'previous_override_revision_id',
        'override_data',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'override_data' => 'array',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Approved profile override revisions are append-only and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new LogicException('Approved profile override revisions are append-only and cannot be deleted.');
        });
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function sourceSubmission(): BelongsTo
    {
        return $this->belongsTo(UmkmUpdateSubmission::class, 'source_submission_id');
    }

    public function approvedReview(): BelongsTo
    {
        return $this->belongsTo(DataValidationReview::class, 'approved_review_id');
    }

    public function previousRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_override_revision_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}