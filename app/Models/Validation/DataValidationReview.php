<?php

namespace App\Models\Validation;

use App\Models\Umkm\UmkmProfileOverrideRevision;
use App\Models\Umkm\UmkmUpdateSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class DataValidationReview extends Model
{
    protected $fillable = [
        'submission_id',
        'reviewer_id',
        'decision',
        'review_note',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Validation review history is append-only and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new LogicException('Validation review history is append-only and cannot be deleted.');
        });
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(UmkmUpdateSubmission::class, 'submission_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function overrideRevision(): HasOne
    {
        return $this->hasOne(UmkmProfileOverrideRevision::class, 'approved_review_id');
    }
}