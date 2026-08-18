@extends('layouts.dashboard')

@section('title', 'Data Usaha Saya')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Data usaha yang tersimpan dan riwayat perubahannya</p>
            <h1 class="h3 mb-2">{{ $effectiveProfile['effective']['business_name'] ?? $umkm->business_name }}</h1>
            <p class="mb-0">Data awal tetap dipertahankan. Perubahan hanya menjadi data saat ini setelah disetujui.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('pelaku-umkm.analytics.index', ['umkm_id' => $umkm->id]) }}">Buka Analitik</a>
            @if(auth()->user()?->hasPermission('umkm.profile.propose'))
                <a class="btn btn-primary" href="{{ route('pelaku-umkm.profile-change.create', $umkm) }}">Ajukan Perubahan</a>
            @endif
            <a class="btn btn-outline-secondary" href="{{ route('pelaku-umkm.umkm.index') }}">Kembali</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Data awal dan data saat ini</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Informasi</th><th>Data awal</th><th>Data saat ini</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($effectiveProfile['labels'] as $field => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ data_get($effectiveProfile, 'source.'.$field) ?? '-' }}</td>
                            <td>{{ data_get($effectiveProfile, 'effective.'.$field) ?? '-' }}</td>
                            <td>
                                @if(in_array($field, $effectiveProfile['overridden_fields'], true))
                                    <span class="badge text-bg-primary">Perubahan disetujui</span>
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
        <div class="card-header"><strong>Informasi sistem — tidak dapat diajukan untuk diubah</strong></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Kode UMKM</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.umkm_code') ?? '-' }}</dd>
                <dt class="col-sm-4">Status Data</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.status_data') ?? '-' }}</dd>
                <dt class="col-sm-4">Kualitas Data</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.quality_status') ?? '-' }}</dd>
                <dt class="col-sm-4">Sumber Data</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.source_system') ?? '-' }}</dd>
                <dt class="col-sm-4">ID Data Sumber</dt><dd class="col-sm-8">{{ data_get($effectiveProfile, 'system_metadata.source_record_id') ?? '-' }}</dd>
            </dl>
        </div>
    </div>

    @if($effectiveProfile['provenance'])
        <div class="alert alert-success mt-3 mb-0">
            Data saat ini memuat perubahan yang telah disetujui pada pengajuan #{{ $effectiveProfile['provenance']['source_submission_id'] }} dan tetap terpisah dari data awal.
        </div>
    @endif
</div>
@endsection