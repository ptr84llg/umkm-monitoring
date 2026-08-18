@extends('layouts.dashboard')

@section('title', 'Detail Pengajuan Akun Pelaku UMKM')

@section('content')
<div class="d-flex flex-column gap-4">
    @if (session('status'))
        <div class="alert alert-success mb-0">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-0">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                <div>
                    <span class="badge text-bg-secondary mb-2">{{ $claim->status }}</span>
                    <h1 class="h3 mb-1">Pengajuan {{ $claim->claim_reference }}</h1>
                    <p class="text-body-secondary mb-0">
                        {{ $claim->umkm?->business_name }} · {{ $claim->umkm?->umkm_code }}
                    </p>
                </div>
                <a class="btn btn-outline-secondary align-self-lg-start"
                   href="{{ route('admin-dinas.account-claims.index') }}">Kembali</a>
            </div>

            <dl class="row mb-0">
                <dt class="col-md-3">Pemohon</dt>
                <dd class="col-md-9">{{ $claim->applicant_name }} · {{ $claim->applicant_email }}</dd>
                <dt class="col-md-3">Keterkaitan</dt>
                <dd class="col-md-9">{{ $claim->relationship_type }}</dd>
                <dt class="col-md-3">Jenis Pengajuan</dt>
                <dd class="col-md-9">{{ $claim->claim_type }}</dd>
                <dt class="col-md-3">Pemeriksaan Dinas</dt>
                <dd class="col-md-9">{{ $claim->reviewedBy?->name ?? 'Belum diperiksa' }}</dd>
                <dt class="col-md-3">Catatan</dt>
                <dd class="col-md-9">{{ $claim->review_note ?: '-' }}</dd>
                <dt class="col-md-3">Akun Teraktivasi</dt>
                <dd class="col-md-9">{{ $claim->activatedUser?->email ?? 'Belum ada' }}</dd>
            </dl>
        </div>
    </section>

    @if ($claim->status === 'pending_review')
        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Periksa Pengajuan</h2>
                <form method="POST"
                      action="{{ route('admin-dinas.account-claims.review', $claim) }}"
                      class="d-grid gap-3">
                    @csrf
                    <div>
                        <label class="form-label" for="review_note">Catatan Pemeriksaan</label>
                        <textarea class="form-control"
                                  id="review_note"
                                  name="review_note"
                                  rows="4"
                                  maxlength="2000">{{ old('review_note') }}</textarea>
                        <div class="form-text">Catatan wajib diisi jika pengajuan ditolak.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-success" type="submit" name="action" value="approve">Setujui dan Kirim Aktivasi</button>
                        <button class="btn btn-outline-danger" type="submit" name="action" value="reject">Tolak Pengajuan</button>
                    </div>
                </form>
            </div>
        </section>
    @endif

    @if ($claim->status === 'approved_pending_activation')
        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-2">Aktivasi Belum Selesai</h2>
                <p class="text-body-secondary">
                    Dinas hanya mengirim ulang kode aktivasi. Pelaku membuat password sendiri, dan keterkaitan akun dengan usaha dibentuk setelah aktivasi berhasil.
                </p>
                <form method="POST" action="{{ route('admin-dinas.account-claims.resend', $claim) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Kirim Ulang Aktivasi</button>
                </form>
            </div>
        </section>
    @endif

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Riwayat Aktivitas</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Aktivitas</th>
                        <th>Perubahan Status</th>
                        <th>Pengguna/Sistem</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($claim->events as $event)
                        <tr>
                            <td>{{ optional($event->event_time)->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $event->event_type }}</td>
                            <td>{{ $event->from_status ?: '-' }} → {{ $event->to_status ?: '-' }}</td>
                            <td>{{ $event->actor?->name ?? 'Pelaku/Sistem' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-body-secondary">Belum ada aktivitas.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection