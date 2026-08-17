@extends('layouts.dashboard')

@section('title', 'Profil Efektif Usaha Saya')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Profil efektif berbasis provenance</p>
            <h1 class="h3 mb-2">{{ $effectiveProfile['effective']['business_name'] ?? $umkm->business_name }}</h1>
            <p class="mb-0">Nilai sumber tetap dipertahankan. Override hanya menjadi nilai efektif setelah persetujuan.</p>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()?->hasPermission('umkm.profile.propose'))
                <a class="btn btn-primary" href="{{ route('pelaku-umkm.profile-change.create', $umkm) }}">Ajukan Perubahan</a>
            @endif
            <a class="btn btn-outline-secondary" href="{{ route('pelaku-umkm.umkm.index') }}">Kembali</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Nilai sumber dan nilai efektif</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Field</th><th>Nilai sumber</th><th>Nilai efektif</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($effectiveProfile['labels'] as $field => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ data_get($effectiveProfile, 'source.'.$field) ?? '-' }}</td>
                            <td>{{ data_get($effectiveProfile, 'effective.'.$field) ?? '-' }}</td>
                            <td>
                                @if(in_array($field, $effectiveProfile['overridden_fields'], true))
                                    <span class="badge text-bg-primary">Approved override</span>
                                @else
                                    <span class="badge text-bg-secondary">Sumber</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Metadata sistem — tidak dapat diajukan oleh Pelaku</strong></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Kode UMKM</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.umkm_code') ?? '-' }}</dd>
                <dt class="col-sm-4">Status Data</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.status_data') ?? '-' }}</dd>
                <dt class="col-sm-4">Mutu Data</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.quality_status') ?? '-' }}</dd>
                <dt class="col-sm-4">Source System</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.source_system') ?? '-' }}</dd>
                <dt class="col-sm-4">Source Record</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.source_record_id') ?? '-' }}</dd>
            </dl>
        </div>
    </div>

    @if($effectiveProfile['provenance'])
        <div class="alert alert-success mt-3 mb-0">
            Nilai efektif saat ini berasal dari submission #{{ $effectiveProfile['provenance']['source_submission_id'] }} dan tetap terpisah dari data sumber.
        </div>
    @endif
</div>
@endsection