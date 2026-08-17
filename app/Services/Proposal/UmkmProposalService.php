<?php

namespace App\Services\Proposal;

use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmUpdateSubmission;
use App\Models\Validation\DataValidationReview;
use App\Services\Audit\AuditLogger;
use App\Services\PelakuUmkm\ApprovedProfileOverrideService;
use App\Services\PelakuUmkm\EffectiveUmkmProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UmkmProposalService
{
    public function __construct(
        private readonly EffectiveUmkmProfileService $profiles,
        private readonly ApprovedProfileOverrideService $approvedOverrides
    ) {
    }

    public function createProposal(array $data, int $actorId): UmkmUpdateSubmission
    {
        $umkm = Umkm::query()->findOrFail((int) $data['umkm_id']);
        $profile = $this->profiles->resolve($umkm);
        $requested = $this->profiles->filterEditable($data);
        $changes = [];

        foreach ($requested as $field => $value) {
            $normalized = $this->normalizeValue($field, $value);
            $current = $this->normalizeValue($field, $profile['effective'][$field] ?? null);

            if ($normalized !== $current) {
                $changes[$field] = $normalized;
            }
        }

        if ($changes === []) {
            throw ValidationException::withMessages([
                'profile' => 'Tidak ada perubahan profil yang berbeda dari nilai efektif saat ini.',
            ]);
        }

        return UmkmUpdateSubmission::query()->create([
            'umkm_id' => $umkm->id,
            'submitted_by' => $actorId,
            'old_data' => $profile['effective'],
            'new_data' => $changes,
            'evidence_path' => $data['evidence_path'] ?? null,
            'submission_payload' => [
                'schema' => 'profile_override.v1',
                'source_values' => $profile['source'],
                'effective_before' => $profile['effective'],
                'system_metadata' => $profile['system_metadata'],
                'editable_fields' => array_keys($changes),
            ],
            'status_data' => $data['status_data'] ?? 'diajukan',
            'submitted_at' => now(),
        ]);
    }

    public function reviewProposal(
        UmkmUpdateSubmission $submission,
        string $decision,
        ?string $reviewNote,
        int $reviewerId,
        Request $request,
        AuditLogger $auditLogger
    ): UmkmUpdateSubmission {
        if (! in_array($decision, ['disetujui', 'perlu_perbaikan', 'ditolak'], true)) {
            throw ValidationException::withMessages(['decision' => 'Keputusan review tidak valid.']);
        }

        return DB::transaction(function () use (
            $submission,
            $decision,
            $reviewNote,
            $reviewerId,
            $request,
            $auditLogger
        ): UmkmUpdateSubmission {
            if ($submission->status_data === 'disetujui') {
                throw ValidationException::withMessages([
                    'decision' => 'Submission yang sudah disetujui tidak dapat direview ulang.',
                ]);
            }

            $before = $submission->toArray();
            $review = DataValidationReview::query()->create([
                'submission_id' => $submission->id,
                'reviewer_id' => $reviewerId,
                'decision' => $decision,
                'review_note' => $reviewNote,
                'reviewed_at' => now(),
            ]);

            $submission->forceFill([
                'status_data' => $decision,
                'review_notes' => $reviewNote,
                'reviewed_at' => $review->reviewed_at,
                'reviewed_by' => $reviewerId,
            ])->save();

            if ($decision === 'disetujui') {
                $revision = $this->approvedOverrides->activateFromApprovedSubmission(
                    $submission->fresh(),
                    $review,
                    $reviewerId
                );

                $auditLogger->log(
                    'umkm.profile.override.approved',
                    $request,
                    'umkm_profile_override_revisions',
                    $revision->id,
                    [],
                    $revision->toArray()
                );
            }

            $updated = $submission->fresh();
            $auditLogger->log(
                'umkm.profile.proposal.review',
                $request,
                'umkm_update_submissions',
                $updated->id,
                $before,
                $updated->toArray()
            );

            return $updated;
        });
    }

    private function normalizeValue(string $field, mixed $value): mixed
    {
        if ($value === '') {
            return null;
        }

        return match ($field) {
            'employee_count', 'marketing_method_id' => $value === null ? null : (int) $value,
            'established_date' => $value === null ? null : (string) $value,
            default => $value === null ? null : trim((string) $value),
        };
    }
}