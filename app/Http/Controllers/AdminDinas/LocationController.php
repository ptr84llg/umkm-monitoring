<?php

namespace App\Http\Controllers\AdminDinas;

use App\Http\Controllers\Controller;
use App\Models\Reference\Region;
use App\Models\Umkm\Umkm;
use App\Models\Umkm\UmkmUpdateSubmission;
use App\Services\Audit\AuditLogger;
use App\Services\Location\MapProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LocationController extends Controller
{
    public function edit(Umkm $umkm, MapProviderService $map)
    {
        $cityCode = $this->cityCode();

        $provinces = Region::query()
            ->active()
            ->level(Region::LEVEL_PROVINCE)
            ->where('code', $this->provinceCode())
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $cities = Region::query()
            ->active()
            ->level(Region::LEVEL_CITY)
            ->where('code', $cityCode)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $districts = Region::query()
            ->active()
            ->level(Region::LEVEL_DISTRICT)
            ->where('city_code', $cityCode)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $villages = Region::query()
            ->active()
            ->level(Region::LEVEL_VILLAGE)
            ->where('city_code', $cityCode)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'parent_code']);

        return view('pages.admin-dinas.location.edit', compact(
            'umkm',
            'provinces',
            'cities',
            'districts',
            'villages'
        ))->with('mapConfig', $map->config());
    }

    public function submitProposal(Request $request, Umkm $umkm, AuditLogger $audit)
    {
        abort_unless(
            in_array($umkm->status_data, $this->operationalStatuses(), true),
            422,
            'Usulan lokasi hanya untuk data operasional Dinas.'
        );

        $validated = $request->validate([
            'province_region_id' => 'required|integer|exists:regions,id',
            'city_region_id' => 'required|integer|exists:regions,id',
            'district_region_id' => 'required|integer|exists:regions,id',
            'village_region_id' => 'required|integer|exists:regions,id',
            'address_detail' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        abort_unless($this->withinLubuklinggauScope(collect($validated)), 422, 'Wilayah lokasi harus berada di Kota Lubuklinggau, Sumatera Selatan.');

        $validated['coordinate_status'] = 'terpetakan';

        $proposal = UmkmUpdateSubmission::query()->create([
            'umkm_id' => $umkm->id,
            'submitted_by' => $request->user()->id,
            'old_data' => ['location' => 'existing'],
            'new_data' => ['location' => $validated],
            'submission_payload' => ['type' => 'location_update'] + $validated,
            'status_data' => 'diajukan',
            'submitted_at' => now(),
        ]);

        $audit->log('umkm.location.proposal.create', $request, 'umkm_update_submissions', $proposal->id, [], $proposal->toArray());

        return back()->with('status', 'Usulan lokasi diajukan.');
    }

    public function adminValidate(Request $request, UmkmUpdateSubmission $proposal, AuditLogger $audit)
    {
        abort_unless($proposal->status_data === 'diajukan', 422, 'Proposal hanya bisa divalidasi dari status diajukan.');

        $validated = $request->validate([
            'decision' => 'required|in:disetujui,perlu_perbaikan,ditolak',
            'review_note' => 'nullable|string|max:1000',
        ]);

        $before = $proposal->toArray();
        $proposal->update([
            'status_data' => $validated['decision'],
            'review_notes' => $validated['review_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $audit->log('umkm.location.proposal.review', $request, 'umkm_update_submissions', $proposal->id, $before, $proposal->fresh()->toArray());

        return back()->with('status', 'Validasi lokasi tersimpan.');
    }

    private function withinLubuklinggauScope(Collection $payload): bool
    {
        $regions = Region::query()
            ->whereIn('id', [
                (int) $payload->get('province_region_id'),
                (int) $payload->get('city_region_id'),
                (int) $payload->get('district_region_id'),
                (int) $payload->get('village_region_id'),
            ])
            ->get()
            ->keyBy('id');

        $province = $regions->get((int) $payload->get('province_region_id'));
        $city = $regions->get((int) $payload->get('city_region_id'));
        $district = $regions->get((int) $payload->get('district_region_id'));
        $village = $regions->get((int) $payload->get('village_region_id'));

        return $province?->code === $this->provinceCode()
            && $province?->level === Region::LEVEL_PROVINCE
            && $city?->code === $this->cityCode()
            && $city?->level === Region::LEVEL_CITY
            && $district?->level === Region::LEVEL_DISTRICT
            && $district?->city_code === $this->cityCode()
            && $village?->level === Region::LEVEL_VILLAGE
            && $village?->city_code === $this->cityCode()
            && $village?->parent_code === $district?->code;
    }

    private function operationalStatuses(): array
    {
        return array_values(array_filter(array_map(
            fn ($status) => trim((string) $status),
            (array) config('umkm.data.operational_statuses', ['resmi', 'terbatas'])
        )));
    }

    private function provinceCode(): string
    {
        return (string) config('umkm.landing_region.province_code', '16');
    }

    private function cityCode(): string
    {
        return (string) config('umkm.landing_region.city_code', '16.73');
    }
}
