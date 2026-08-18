@extends('layouts.dashboard')

@section('title', 'Data Usaha Saya')

@section('content')
<div class="container-fluid py-3">
    <div class="mb-4">
        <p class="text-muted mb-1">Usaha yang terhubung dengan akun Anda</p>
        <h1 class="h3 mb-2">Data Usaha Saya</h1>
        <p class="mb-0">Daftar ini hanya memuat usaha yang telah diverifikasi dan terhubung dengan akun Anda. Data dapat dilihat; perubahan diajukan melalui menu perubahan profil.</p>
    </div>

    <div class="card">
        <div class="card-body p-0">
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
                        @forelse ($umkms as $umkm)
                            <tr>
                                <td>{{ $umkm->umkm_code }}</td>
                                <td>{{ $umkm->business_name }}</td>
                                <td>{{ $umkm->status_data ?? '-' }}</td>
                                <td>{{ $umkm->quality_status ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('pelaku-umkm.umkm.show', $umkm) }}">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Tidak ada UMKM terverifikasi yang dapat ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $umkms->links() }}
    </div>
</div>
@endsection