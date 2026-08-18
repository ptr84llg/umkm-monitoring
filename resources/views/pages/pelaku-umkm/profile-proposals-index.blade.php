@extends('layouts.dashboard')

@section('title', 'Riwayat Pengajuan Profil')

@section('content')
<div class="container-fluid py-3">
    <div class="mb-4">
        <p class="text-muted mb-1">Riwayat pengajuan tersimpan</p>
        <h1 class="h3">Riwayat Perubahan Data Usaha</h1>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>ID</th><th>UMKM</th><th>Status</th><th>Diajukan</th><th></th></tr></thead>
                <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td>#{{ $submission->id }}</td>
                        <td>{{ $submission->umkm?->business_name ?? '-' }}</td>
                        <td>{{ $submission->status_data }}</td>
                        <td>{{ $submission->submitted_at?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td><a href="{{ route('pelaku-umkm.profile-proposals.show', $submission) }}">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pengajuan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($submissions->hasPages())<div class="card-footer">{{ $submissions->links() }}</div>@endif
    </div>
</div>
@endsection