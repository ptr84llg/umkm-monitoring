<?php

namespace App\Http\Requests\AdminDinas;

use Illuminate\Foundation\Http\FormRequest;

class ReviewProfileOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('umkm.profile.review') ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:disetujui,perlu_perbaikan,ditolak'],
            'review_note' => [
                'nullable',
                'string',
                'max:2000',
                'required_if:decision,perlu_perbaikan,ditolak',
            ],
        ];
    }
}