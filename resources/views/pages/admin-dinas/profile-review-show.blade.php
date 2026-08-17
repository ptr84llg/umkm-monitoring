@extends('layouts.dashboard')

@section('title', 'Detail Review Profil UMKM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Submission #{{ $proposal->id }}</p>
            <h1 class="h3 mb-2">{{ $proposal->umkm?->business_name ?? 'UMKM' }}</h1>
            <p class="mb-0">Status: <strong>{{ $proposal->status_data }}</strong></p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin-dinas.profile-reviews.index', ['status' => $proposal->status_data]) }}">Kembali</a>
    </div>

    @if($conflictingFields !== [] && $proposal->status_data === 'diajukan')
        <div class="alert alert-warning">
            Profil efektif telah berubah sejak submission pada field:
            <strong>{{ implode(', ', array_map(fn ($field) => $labels[$field] ?? $field, $conflictingFields)) }}</strong>.
            Approval diblokir. Gunakan keputusan perlu perbaikan atau ditolak, lalu minta Pelaku membuat submission baru.
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><strong>Nilai efektif saat pengajuan</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        @foreach($labels as $field => $label)
                            <dt class="col-sm-5">{{ $label }}</dt>
                            <dd class="col-sm-7">{{ data_get($proposal->old_data, $field) ?? '-' }}</dd>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><strong>Perubahan yang diajukan</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        @forelse(($proposal->new_data ?? []) as $field => $value)
                            <dt class="col-sm-5">{{ $labels[$field] ?? $field }}</dt>
                            <dd class="col-sm-7">{{ $value ?? '-' }}</dd>
                        @empty
                            <dd class="col-12 text-muted mb-0">Tidak ada perubahan.</dd>
                        @endforelse
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Profil efektif saat ini</strong></div>
        <div class="card-body">
            <dl class="row mb-0">
                @foreach($labels as $field => $label)
                    <dt class="col-sm-4">{{ $label }}</dt>
                    <dd class="col-sm-8">
                        {{ data_get($currentProfile, 'effective.'.$field) ?? '-' }}
                        @if(in_array($field, $currentProfile['overridden_fields'] ?? [], true))
                            <span class="badge text-bg-info ms-1">approved override</span>
                        @endif
                    </dd>
                @endforeach
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Riwayat review</strong></div>
        <div class="card-body">
            @forelse($proposal->reviews->sortBy('id') as $review)
                <div class="border-bottom pb-2 mb-2">
                    <strong>{{ $review->decision }}</strong>
                    — {{ $review->reviewed_at?->format('d-m-Y H:i') ?? '-' }}
                    — {{ $review->reviewer?->name ?? 'Reviewer' }}
                    @if($review->review_note)<div>{{ $review->review_note }}</div>@endif
                </div>
            @empty
                <p class="text-muted mb-0">Belum ada keputusan review.</p>
            @endforelse
        </div>
    </div>

    @if($proposal->status_data === 'diajukan')
        <div class="card">
            <div class="card-header"><strong>Keputusan Admin Dinas</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin-dinas.profile-reviews.review', $proposal) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="decision">Keputusan</label>
                        <select class="form-select @error('decision') is-invalid @enderror" id="decision" name="decision" required>
                            <option value="">Pilih keputusan</option>
                            <option value="disetujui" @disabled($conflictingFields !== []) @selected(old('decision') === 'disetujui')>Disetujui</option>
                            <option value="perlu_perbaikan" @selected(old('decision') === 'perlu_perbaikan')>Perlu perbaikan</option>
                            <option value="ditolak" @selected(old('decision') === 'ditolak')>Ditolak</option>
                        </select>
                        @error('decision')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="review_note">Catatan review</label>
                        <textarea class="form-control @error('review_note') is-invalid @enderror" id="review_note" name="review_note" rows="4" maxlength="2000">{{ old('review_note') }}</textarea>
                        <div class="form-text">Wajib untuk keputusan perlu perbaikan atau ditolak.</div>
                        @error('review_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary" type="submit">Simpan Keputusan</button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-secondary mb-0">Submission ini sudah memiliki keputusan. Koreksi berikutnya harus melalui submission baru.</div>
    @endif
</div>
@endsection