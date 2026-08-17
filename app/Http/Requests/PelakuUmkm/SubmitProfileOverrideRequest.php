<?php

namespace App\Http\Requests\PelakuUmkm;

use App\Models\Umkm\Umkm;
use App\Services\PelakuUmkm\PelakuWorkspaceAccessService;
use Illuminate\Foundation\Http\FormRequest;

class SubmitProfileOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $umkm = $this->route('umkm');

        return $user !== null
            && $umkm instanceof Umkm
            && $user->hasPermission('umkm.profile.propose')
            && app(PelakuWorkspaceAccessService::class)->owns($user, $umkm);
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:150'],
            'established_date' => ['nullable', 'date'],
            'employee_count' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'marketing_method_id' => ['nullable', 'integer', 'exists:marketing_method_references,id'],

            'quality_status' => ['prohibited'],
            'status_data' => ['prohibited'],
            'source_system' => ['prohibited'],
            'source_record_id' => ['prohibited'],
            'source_active' => ['prohibited'],
            'source_snapshot' => ['prohibited'],
            'notes' => ['prohibited'],
        ];
    }
}