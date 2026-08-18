@extends('layouts.dashboard')

@section('title', 'Verifikasi Akun Pelaku UMKM')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <span class="badge text-bg-primary mb-2">Verifikasi Akun Pelaku</span>
                    <h1 class="h3 mb-2">Verifikasi dan Aktivasi Akun Pelaku</h1>
                    <p class="text-body-secondary mb-0">
                        Verifikasi keterkaitan pemohon dengan UMKM. Persetujuan tidak mengubah data sumber LSS
                        dan keterkaitan akun dengan usaha dibentuk setelah aktivasi berhasil.
                    </p>
                </div>
                <a class="btn btn-primary align-self-lg-start"
                   href="{{ route('admin-dinas.account-claims.invite') }}">Kirim Undangan Aktivasi</a>
            </div>
        </div>
    </section>

    @if (session('status'))
        <div class="alert alert-success mb-0">{{ session('status') }}</div>
    @endif

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <form class="row g-3 mb-4" method="GET">
                <div class="col-md-5">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua</option>
                        @foreach ([
                            'pending_review' => 'Menunggu Verifikasi',
                            'approved_pending_activation' => 'Disetujui, Menunggu Aktivasi Akun',
                            'rejected' => 'Ditolak',
                            'activated' => 'Akun Aktif',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit">Terapkan</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin-dinas.account-claims.index') }}">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Referensi</th>
                        <th>UMKM</th>
                        <th>Pemohon</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>Diajukan</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($claims as $claim)
                        <tr>
                            <td><code>{{ $claim->claim_reference }}</code></td>
                            <td>
                                <strong>{{ $claim->umkm?->business_name ?? '-' }}</strong>
                                <div class="small text-body-secondary">{{ $claim->umkm?->umkm_code }}</div>
                            </td>
                            <td>
                                {{ $claim->applicant_name }}
                                <div class="small text-body-secondary">{{ $claim->applicant_email }}</div>
                            </td>
                            <td>{{ $claim->claim_type === 'dinas_invite' ? 'Undangan Dinas' : 'Pengajuan Mandiri' }}</td>
                            <td><span class="badge text-bg-secondary">{{ $claim->status }}</span></td>
                            <td>{{ optional($claim->submitted_at)->format('d-m-Y H:i') }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="{{ route('admin-dinas.account-claims.show', $claim) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary py-4">Belum ada pengajuan akun pada konteks ini.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $claims->links() }}
        </div>
    </section>
</div>
@endsection