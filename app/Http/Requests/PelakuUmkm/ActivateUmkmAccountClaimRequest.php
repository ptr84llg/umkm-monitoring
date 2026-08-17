<?php

namespace App\Http\Requests\PelakuUmkm;

use Illuminate\Foundation\Http\FormRequest;

class ActivateUmkmAccountClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activation_token' => ['required', 'string', 'min:40', 'max:128'],
            'otp' => ['required', 'digits:6'],
            'password' => ['nullable', 'string', 'min:12', 'max:255', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'activation_token.required' => 'Token aktivasi tidak tersedia.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 digit.',
            'password.min' => 'Password minimal terdiri dari 12 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ];
    }
}