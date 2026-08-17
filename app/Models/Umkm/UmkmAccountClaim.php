<?php

namespace App\Models\Umkm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class UmkmAccountClaim extends Model
{
    public const TYPE_SELF_CLAIM = 'self_claim';
    public const TYPE_DINAS_INVITE = 'dinas_invite';

    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED_PENDING_ACTIVATION = 'approved_pending_activation';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACTIVATED = 'activated';

    protected $fillable = [
        'umkm_id',
        'claim_reference',
        'claim_type',
        'applicant_name',
        'applicant_email',
        'relationship_type',
        'status',
        'activated_user_id',
        'resubmission_of_id',
        'submitted_by_user_id',
        'reviewed_by_user_id',
        'review_note',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'activation_completed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'activation_completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Account claim history is append-preserved and cannot be deleted.');
        });
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function activatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_user_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function resubmissionOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resubmission_of_id');
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(UmkmClaimActivationChallenge::class, 'claim_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(UmkmAccountClaimEvent::class, 'claim_id');
    }
}