<?php

namespace App\Http\Requests\AdminDinas;

use Illuminate\Foundation\Http\FormRequest;

class InviteUmkmAccountClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('umkm.claim.review');
    }

    public function rules(): array
    {
        return [
            'umkm_code' => ['required', 'string', 'max:100'],
            'applicant_name' => ['required', 'string', 'max:190'],
            'applicant_email' => ['required', 'email:rfc', 'max:190'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}