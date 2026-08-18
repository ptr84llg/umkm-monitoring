@extends('layouts.dashboard')

@section('title', 'Detail Pengajuan Profil')

@section('content')
@php
    $statusLabel = static fn (?string $status): string => match ($status) {
        'diajukan' => 'Menunggu Pemeriksaan',
        'disetujui' => 'Disetujui',
        'perlu_perbaikan' => 'Perlu Perbaikan',
        'ditolak' => 'Ditolak',
        default => 'Status belum tersedia',
    };
    $decisionLabel = static fn (?string $decision): string => match ($decision) {
        'disetujui' => 'Disetujui',
        'perlu_perbaikan' => 'Perlu Perbaikan',
        'ditolak' => 'Ditolak',
        default => 'Keputusan belum tersedia',
    };
    $fieldLabels = [
        'business_name' => 'Nama Usaha',
        'established_date' => 'Tanggal Berdiri',
        'employee_count' => 'Jumlah Tenaga Kerja',
        'marketing_method_id' => 'Metode Pemasaran',
    ];
@endphp
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Pengajuan #{{ $proposal->id }}</p>
            <h1 class="h3">{{ $proposal->umkm?->business_name ?? 'UMKM' }}</h1>
            <p class="mb-0">Status: <strong>{{ $statusLabel($proposal->status_data) }}</strong></p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('pelaku-umkm.profile-proposals.index') }}">Kembali</a>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Nilai yang diajukan</strong></div>
        <div class="card-body">
            <dl class="row mb-0">
                @forelse(($proposal->new_data ?? []) as $field => $value)
                    <dt class="col-sm-4">{{ $fieldLabels[$field] ?? 'Informasi Usaha' }}</dt><dd class="col-sm-8">{{ is_scalar($value) || $value === null ? ($value ?? '-') : 'Data tersimpan' }}</dd>
                @empty
                    <dd class="col-12 text-muted mb-0">Tidak ada data perubahan yang diajukan.</dd>
                @endforelse
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Riwayat pemeriksaan</strong></div>
        <div class="card-body">
            @forelse($proposal->reviews->sortBy('id') as $review)
                <div class="border-bottom pb-2 mb-2">
                    <strong>{{ $decisionLabel($review->decision) }}</strong> — {{ $review->reviewed_at?->format('d-m-Y H:i') ?? '-' }}
                    @if($review->review_note)<div>{{ $review->review_note }}</div>@endif
                </div>
            @empty
                <p class="text-muted mb-0">Belum diperiksa Admin Dinas.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection