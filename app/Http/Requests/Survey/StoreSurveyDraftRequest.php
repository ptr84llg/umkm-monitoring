<?php
namespace App\Http\Requests\Survey;
use Illuminate\Foundation\Http\FormRequest;
class StoreSurveyDraftRequest extends FormRequest { public function authorize(): bool { return $this->user()?->hasPermission('survey.fill') ?? false; } public function rules(): array { return ['answers'=>'required|array']; }}

