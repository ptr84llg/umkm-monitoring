<?php
namespace App\Http\Requests\ValidasiAhli;
use Illuminate\Foundation\Http\FormRequest;
class StoreAssessmentDraftRequest extends FormRequest { public function authorize(): bool { return $this->user()?->hasPermission('validation.expert.fill') ?? false; } public function rules(): array { return ['score'=>'nullable|numeric|min:0|max:100','notes'=>'nullable|string|max:5000']; }}

