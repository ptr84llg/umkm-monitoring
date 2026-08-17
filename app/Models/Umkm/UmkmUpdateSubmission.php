<?php

namespace App\Models\Umkm;

use App\Models\User;
use App\Models\Validation\DataValidationReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class UmkmUpdateSubmission extends Model
{
    protected $fillable = [
        'umkm_id',
        'submitted_by',
        'old_data',
        'new_data',
        'evidence_path',
        'submission_payload',
        'status_data',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'submission_payload' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $submission): void {
            foreach ([
                'umkm_id',
                'submitted_by',
                'old_data',
                'new_data',
                'submission_payload',
                'submitted_at',
            ] as $column) {
                if ($submission->isDirty($column)) {
                    throw new LogicException("Submitted profile proposal history is immutable: {$column}.");
                }
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Profile proposal history is append-preserved and cannot be deleted.');
        });
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DataValidationReview::class, 'submission_id');
    }

    public function overrideRevision(): HasOne
    {
        return $this->hasOne(UmkmProfileOverrideRevision::class, 'source_submission_id');
    }
}