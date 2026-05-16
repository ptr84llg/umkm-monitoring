<?php
namespace App\Http\Requests\AdminDinas;
use Illuminate\Foundation\Http\FormRequest;
class ReviewUmkmProposalRequest extends FormRequest { public function authorize(): bool { return $this->user()?->hasPermission('umkm.review.update') ?? false; } public function rules(): array { return ['decision'=>'required|in:disetujui,perlu_perbaikan,ditolak','review_note'=>'nullable|string|max:2000']; }}

