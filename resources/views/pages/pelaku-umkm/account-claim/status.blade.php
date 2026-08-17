@extends('layouts.auth')

@php
    $assetProfile = 'base';
    $labels = [
        'pending_review' => ['Menunggu Review Dinas', 'warning'],
        'approved_pending_activation' => ['Disetujui, Menunggu Aktivasi', 'info'],
        'rejected' => ['Ditolak', 'danger'],
        'activated' => ['Kredensial Teraktivasi', 'success'],
    ];
    [$statusLabel, $statusClass] = $labels[$claim->status] ?? [$claim->status, 'secondary'];
@endphp

@section('title', 'Status Klaim Pelaku UMKM | SISFODA')

@section('content')
<div class="container py-5" style="max-width: 760px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <span class="badge text-bg-{{ $statusClass }} mb-3">{{ $statusLabel }}</span>
            <h1 class="h3 mb-3">Status Klaim Akun Pelaku UMKM</h1>

            <dl class="row mb-4">
                <dt class="col-sm-4">Referensi</dt>
                <dd class="col-sm-8"><code>{{ $claim->claim_reference }}</code></dd>
                <dt class="col-sm-4">Jenis</dt>
                <dd class="col-sm-8">{{ $claim->claim_type === 'dinas_invite' ? 'Undangan Dinas' : 'Klaim Mandiri' }}</dd>
                <dt class="col-sm-4">Diajukan</dt>
                <dd class="col-sm-8">{{ optional($claim->submitted_at)->format('d-m-Y H:i') }}</dd>
            </dl>

            @if ($claim->status === 'pending_review')
                <p class="text-body-secondary">Dinas akan memverifikasi keterkaitan pemohon dengan UMKM sebelum aktivasi dapat dikirim.</p>
            @elseif ($claim->status === 'approved_pending_activation')
                <p class="text-body-secondary">Buka tautan aktivasi yang dikirim ke email pemohon dan masukkan OTP pada masa berlaku yang tersedia.</p>
            @elseif ($claim->status === 'rejected')
                <p class="text-body-secondary">Pengajuan ditolak. Histori pengajuan tetap disimpan dan pengajuan baru dapat dibuat sebagai resubmission.</p>
            @elseif ($claim->status === 'activated')
                <p class="text-body-secondary">
                    Kredensial akun telah aktif. Ownership binding belum dibentuk pada Checkpoint 10A,
                    sehingga workspace Pelaku belum diaktifkan.
                </p>
            @endif

            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ url('/') }}">Beranda</a>
                @if ($claim->status === 'rejected')
                    <a class="btn btn-primary" href="{{ route('pelaku-claim.create') }}">Ajukan Ulang</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection