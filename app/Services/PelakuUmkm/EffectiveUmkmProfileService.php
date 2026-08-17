<?php

namespace App\Services\PelakuUmkm;

use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmCurrentProfileOverride;

class EffectiveUmkmProfileService
{
    public const EDITABLE_FIELDS = [
        'business_name' => 'Nama Usaha',
        'established_date' => 'Tanggal Berdiri',
        'employee_count' => 'Jumlah Tenaga Kerja',
        'marketing_method_id' => 'Metode Pemasaran',
    ];

    public function sourceProfile(Umkm $umkm): array
    {
        $baseline = $umkm->baselineProfile()->first();

        return [
            'values' => [
                'business_name' => $umkm->business_name,
                'established_date' => $umkm->established_date?->format('Y-m-d'),
                'employee_count' => $baseline?->employee_count,
                'marketing_method_id' => $baseline?->marketing_method_id,
            ],
            'system_metadata' => [
                'umkm_code' => $umkm->umkm_code,
                'status_data' => $umkm->getAttribute('status_data'),
                'quality_status' => $umkm->getAttribute('quality_status'),
                'source_system' => $umkm->getAttribute('source_system'),
                'source_record_id' => $umkm->getAttribute('source_record_id'),
                'source_active' => $umkm->getAttribute('source_active'),
            ],
        ];
    }

    public function resolve(Umkm $umkm): array
    {
        $source = $this->sourceProfile($umkm);
        $current = UmkmCurrentProfileOverride::query()
            ->with('revision')
            ->where('umkm_id', $umkm->id)
            ->first();

        $overrideData = $this->filterEditable($current?->revision?->override_data ?? []);
        $effective = $source['values'];

        foreach ($overrideData as $field => $value) {
            $effective[$field] = $value;
        }

        return [
            'source' => $source['values'],
            'effective' => $effective,
            'system_metadata' => $source['system_metadata'],
            'labels' => self::EDITABLE_FIELDS,
            'overridden_fields' => array_keys($overrideData),
            'provenance' => $current?->revision ? [
                'override_revision_id' => $current->revision->id,
                'source_submission_id' => $current->revision->source_submission_id,
                'approved_review_id' => $current->revision->approved_review_id,
                'approved_by_user_id' => $current->revision->approved_by_user_id,
                'approved_at' => $current->revision->approved_at?->toIso8601String(),
            ] : null,
        ];
    }

    public function filterEditable(array $data): array
    {
        return array_intersect_key($data, self::EDITABLE_FIELDS);
    }
}