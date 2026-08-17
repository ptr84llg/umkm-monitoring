@extends('layouts.dashboard')

@section('title', 'Dashboard Pelaku UMKM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Workspace kepemilikan terverifikasi</p>
            <h1 class="h3 mb-2">Dashboard Pelaku UMKM</h1>
            <p class="mb-0">Data pada tahap ini ditampilkan secara read-only. Perubahan profil belum diaktifkan.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('pelaku-umkm.umkm.index') }}">Lihat Data Usaha</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="text-muted small">UMKM dengan binding aktif dan terverifikasi</div>
            <div class="display-6 fw-semibold">{{ number_format($ownedCount) }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Usaha Saya</strong>
        </div>
        <div class="card-body p-0">
            @if ($umkms->isEmpty())
                <div class="p-4 text-muted">Tidak ada binding UMKM aktif yang dapat ditampilkan.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode UMKM</th>
                                <th>Nama Usaha</th>
                                <th>Status Data</th>
                                <th>Mutu Data</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($umkms as $umkm)
                                <tr>
                                    <td>{{ $umkm->umkm_code }}</td>
                                    <td>{{ $umkm->business_name }}</td>
                                    <td>{{ $umkm->status_data ?? '-' }}</td>
                                    <td>{{ $umkm->quality_status ?? '-' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('pelaku-umkm.umkm.show', $umkm) }}">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection