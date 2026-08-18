@extends('layouts.dashboard')

@section('title', 'Verifikasi Perubahan Data UMKM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Verifikasi Perubahan Data</p>
            <h1 class="h3 mb-2">Verifikasi Perubahan Data UMKM</h1>
            <p class="mb-0">Persetujuan hanya mengaktifkan perubahan yang disetujui. Data sumber/LSS tetap dipertahankan.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin-dinas.profile-reviews.index') }}" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    @foreach($statuses as $item)
                        <option value="{{ $item }}" @selected($status === $item)>{{ str_replace('_', ' ', ucfirst($item)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary" type="submit">Tampilkan</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>UMKM</th>
                        <th>Pelaku</th>
                        <th>Diajukan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $proposal)
                        <tr>
                            <td>#{{ $proposal->id }}</td>
                            <td>
                                <strong>{{ $proposal->umkm?->business_name ?? '-' }}</strong>
                                <div class="small text-muted">{{ $proposal->umkm?->umkm_code ?? '-' }}</div>
                            </td>
                            <td>
                                {{ $proposal->submittedBy?->name ?? '-' }}
                                <div class="small text-muted">{{ $proposal->submittedBy?->email ?? '-' }}</div>
                            </td>
                            <td>{{ $proposal->submitted_at?->format('d-m-Y H:i') ?? '-' }}</td>
                            <td>{{ $proposal->status_data }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin-dinas.profile-reviews.show', $proposal) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada pengajuan pada status ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($submissions->hasPages())
            <div class="card-footer">{{ $submissions->links() }}</div>
        @endif
    </div>
</div>
@endsection