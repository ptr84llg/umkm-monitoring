@extends('layouts.dashboard')

@section('title', 'Undangan Aktivasi Pelaku UMKM')

@section('content')
<div class="d-flex flex-column gap-4">
    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between gap-3 mb-4">
                <div>
                    <span class="badge text-bg-primary mb-2">Undangan Dinas</span>
                    <h1 class="h3 mb-2">Kirim Undangan Aktivasi</h1>
                    <p class="text-body-secondary mb-0">
                        Gunakan hanya setelah Dinas memverifikasi keterkaitan calon Pelaku dengan UMKM.
                        Pelaku membuat kata sandi sendiri saat mengaktifkan akun. Keterkaitan akun dengan usaha dibentuk setelah aktivasi berhasil.
                    </p>
                </div>
                <a class="btn btn-outline-secondary align-self-start"
                   href="{{ route('admin-dinas.account-claims.index') }}">Kembali</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin-dinas.account-claims.invite.store') }}" class="d-grid gap-3">
                @csrf

                <div>
                    <label class="form-label" for="umkm_code">Kode UMKM</label>
                    <input class="form-control"
                           id="umkm_code"
                           name="umkm_code"
                           value="{{ old('umkm_code') }}"
                           maxlength="100"
                           required>
                </div>

                <div>
                    <label class="form-label" for="applicant_name">Nama Pelaku</label>
                    <input class="form-control"
                           id="applicant_name"
                           name="applicant_name"
                           value="{{ old('applicant_name') }}"
                           maxlength="190"
                           required>
                </div>

                <div>
                    <label class="form-label" for="applicant_email">Email Pelaku</label>
                    <input type="email"
                           class="form-control"
                           id="applicant_email"
                           name="applicant_email"
                           value="{{ old('applicant_email') }}"
                           maxlength="190"
                           required>
                </div>

                <div>
                    <label class="form-label" for="review_note">Catatan Verifikasi Dinas</label>
                    <textarea class="form-control"
                              id="review_note"
                              name="review_note"
                              rows="4"
                              maxlength="2000">{{ old('review_note') }}</textarea>
                </div>

                <button class="btn btn-primary" type="submit">Buat Undangan dan Kirim Aktivasi</button>
            </form>
        </div>
    </section>
</div>
@endsection