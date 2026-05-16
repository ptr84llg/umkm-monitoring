<?php
namespace App\Services\AdminDinas;
use App\Models\Umkm;use Illuminate\Support\Arr;
class UmkmOfficialService { public function createOfficial(array $data, int $actorId): Umkm { $data['created_by']=$actorId; $data['updated_by']=$actorId; return Umkm::query()->create(Arr::only($data,['umkm_code','business_name','status_data','quality_status','notes','created_by','updated_by'])); } public function updateOfficial(Umkm $umkm, array $data, int $actorId): Umkm { $data['updated_by']=$actorId; $umkm->update(Arr::only($data,['business_name','status_data','quality_status','notes','updated_by'])); return $umkm->fresh(); }}
