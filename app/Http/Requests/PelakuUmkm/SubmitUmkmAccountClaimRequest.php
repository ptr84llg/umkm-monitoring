<?php

namespace App\Http\Requests\PelakuUmkm;

use Illuminate\Foundation\Http\FormRequest;

class SubmitUmkmAccountClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'umkm_code' => ['required', 'string', 'max:100'],
            'applicant_name' => ['required', 'string', 'max:190'],
            'applicant_email' => ['required', 'email:rfc', 'max:190'],
            'ownership_declaration' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'umkm_code.required' => 'Kode UMKM wajib diisi.',
            'applicant_name.required' => 'Nama pemohon wajib diisi.',
            'applicant_email.required' => 'Email pemohon wajib diisi.',
            'applicant_email.email' => 'Format email pemohon tidak valid.',
            'ownership_declaration.accepted' => 'Pernyataan keterkaitan dengan UMKM wajib disetujui.',
        ];
    }
}