@extends('layouts.dashboard')

@section('title', 'Dashboard Pelaku UMKM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Ruang usaha Anda</p>
            <h1 class="h3 mb-2">Dashboard Pelaku UMKM</h1>
            <p class="mb-0">Lihat data usaha, ajukan perubahan jika ada informasi yang perlu diperbarui, dan gunakan perbandingan untuk memahami kondisi usaha berdasarkan data yang tersedia saat ini.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('pelaku-umkm.analytics.index') }}">Buka Perbandingan</a>
            <a class="btn btn-outline-primary" href="{{ route('pelaku-umkm.umkm.index') }}">Lihat Data Usaha</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="text-muted small">Usaha yang terhubung dengan akun Anda</div>
            <div class="display-6 fw-semibold">{{ number_format($ownedCount) }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Usaha Saya</strong>
        </div>
        <div class="card-body p-0">
            @if ($umkms->isEmpty())
                <div class="p-4 text-muted">Belum ada usaha terverifikasi yang dapat ditampilkan.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode UMKM</th>
                                <th>Nama Usaha</th>
                                <th>Status Data</th>
                                <th>Kualitas Data</th>
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