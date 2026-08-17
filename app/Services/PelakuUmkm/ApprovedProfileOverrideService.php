<?php

namespace App\Services\PelakuUmkm;

use App\Models\Umkm\UmkmCurrentProfileOverride;
use App\Models\Umkm\UmkmProfileOverrideRevision;
use App\Models\Umkm\UmkmUpdateSubmission;
use App\Models\Validation\DataValidationReview;
use Illuminate\Support\Facades\DB;
use LogicException;

class ApprovedProfileOverrideService
{
    public function __construct(
        private readonly EffectiveUmkmProfileService $profiles
    ) {
    }

    public function activateFromApprovedSubmission(
        UmkmUpdateSubmission $submission,
        DataValidationReview $review,
        int $reviewerId
    ): UmkmProfileOverrideRevision {
        return DB::transaction(function () use ($submission, $review, $reviewerId): UmkmProfileOverrideRevision {
            if ($submission->status_data !== 'disetujui') {
                throw new LogicException('Only an approved submission can become an effective profile override.');
            }

            if (data_get($submission->submission_payload, 'schema') !== 'profile_override.v1') {
                throw new LogicException('Legacy or unversioned submissions cannot become profile overrides without explicit reconciliation.');
            }

            if ($review->decision !== 'disetujui' || (int) $review->submission_id !== (int) $submission->id) {
                throw new LogicException('Approved override requires the matching approved review record.');
            }

            if (UmkmProfileOverrideRevision::query()->where('source_submission_id', $submission->id)->exists()) {
                throw new LogicException('This approved submission already produced an override revision.');
            }

            $overrideData = $this->profiles->filterEditable($submission->new_data ?? []);
            if ($overrideData === []) {
                throw new LogicException('Approved submission does not contain an editable profile field.');
            }

            $current = UmkmCurrentProfileOverride::query()
                ->where('umkm_id', $submission->umkm_id)
                ->first();

            $revision = UmkmProfileOverrideRevision::query()->create([
                'umkm_id' => $submission->umkm_id,
                'source_submission_id' => $submission->id,
                'approved_review_id' => $review->id,
                'previous_override_revision_id' => $current?->override_revision_id,
                'override_data' => $overrideData,
                'approved_by_user_id' => $reviewerId,
                'approved_at' => $review->reviewed_at ?? now(),
            ]);

            UmkmCurrentProfileOverride::query()->updateOrCreate(
                ['umkm_id' => $submission->umkm_id],
                [
                    'override_revision_id' => $revision->id,
                    'updated_by_user_id' => $reviewerId,
                ]
            );

            return $revision;
        });
    }
}