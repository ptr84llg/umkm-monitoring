<?php
namespace App\Http\Requests\AdminDinas;
use Illuminate\Foundation\Http\FormRequest;
class StoreUmkmRequest extends FormRequest { public function authorize(): bool { return $this->user()?->hasPermission('umkm.write.official') ?? false; } public function rules(): array { return ['umkm_code'=>'required|string|max:50|unique:umkms,umkm_code','business_name'=>'required|string|max:150','status_data'=>'required|in:draft,diajukan,perlu_perbaikan,ditolak,disetujui,resmi,terbatas','quality_status'=>'nullable|string|max:50']; }}

