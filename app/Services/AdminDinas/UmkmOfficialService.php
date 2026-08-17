<?php

namespace App\Services\AdminDinas;

use App\Models\Umkm\Umkm;
use Illuminate\Support\Arr;
use LogicException;

class UmkmOfficialService
{
    public function createOfficial(array $data, int $actorId): Umkm
    {
        $data['created_by'] = $actorId;
        $data['updated_by'] = $actorId;

        return Umkm::query()->create(Arr::only($data, [
            'umkm_code',
            'business_name',
            'status_data',
            'quality_status',
            'notes',
            'created_by',
            'updated_by',
        ]));
    }

    public function updateOfficial(Umkm $umkm, array $data, int $actorId): Umkm
    {
        $this->assertNotSourceOwned($umkm);
        $data['updated_by'] = $actorId;

        $umkm->update(Arr::only($data, [
            'business_name',
            'status_data',
            'quality_status',
            'notes',
            'updated_by',
        ]));

        return $umkm->fresh();
    }

    private function assertNotSourceOwned(Umkm $umkm): void
    {
        if ($umkm->getAttribute('source_system') !== null
            || $umkm->getAttribute('source_record_id') !== null) {
            throw new LogicException(
                'Source-owned UMKM cannot be directly updated. Use the approved profile override workflow.'
            );
        }
    }
}