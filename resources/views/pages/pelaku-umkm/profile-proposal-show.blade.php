@extends('layouts.dashboard')

@section('title', 'Detail Pengajuan Profil')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Submission #{{ $proposal->id }}</p>
            <h1 class="h3">{{ $proposal->umkm?->business_name ?? 'UMKM' }}</h1>
            <p class="mb-0">Status: <strong>{{ $proposal->status_data }}</strong></p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('pelaku-umkm.profile-proposals.index') }}">Kembali</a>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Nilai yang diajukan</strong></div>
        <div class="card-body">
            <dl class="row mb-0">
                @forelse(($proposal->new_data ?? []) as $field => $value)
                    <dt class="col-sm-4">{{ $field }}</dt><dd class="col-sm-8">{{ is_scalar($value) || $value === null ? ($value ?? '-') : json_encode($value) }}</dd>
                @empty
                    <dd class="col-12 text-muted mb-0">Tidak ada payload perubahan.</dd>
                @endforelse
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Riwayat review</strong></div>
        <div class="card-body">
            @forelse($proposal->reviews->sortBy('id') as $review)
                <div class="border-bottom pb-2 mb-2">
                    <strong>{{ $review->decision }}</strong> — {{ $review->reviewed_at?->format('d-m-Y H:i') ?? '-' }}
                    @if($review->review_note)<div>{{ $review->review_note }}</div>@endif
                </div>
            @empty
                <p class="text-muted mb-0">Belum direview Admin Dinas. Route review baru diaktifkan pada Checkpoint 10E.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection