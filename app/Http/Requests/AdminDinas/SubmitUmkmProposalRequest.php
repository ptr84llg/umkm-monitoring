<?php
namespace App\Http\Requests\AdminDinas;
use Illuminate\Foundation\Http\FormRequest;
class SubmitUmkmProposalRequest extends FormRequest { public function authorize(): bool { return $this->user()?->hasPermission('umkm.submit.update') ?? false; } public function rules(): array { return ['umkm_id'=>'required|integer|exists:umkms,id','proposed_business_name'=>'nullable|string|max:150','proposed_quality_status'=>'nullable|string|max:50','proposed_notes'=>'nullable|string','status_data'=>'required|in:draft,diajukan','evidence_path'=>'nullable|string|max:255']; }}

