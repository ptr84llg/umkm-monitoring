@extends('layouts.auth')

@php
    $assetProfile = 'base';
@endphp

@section('title', 'Klaim Akun Pelaku UMKM | SISFODA')

@section('content')
<div class="container py-5" style="max-width: 760px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <div class="mb-4">
                <span class="badge text-bg-primary mb-2">Checkpoint 10A</span>
                <h1 class="h3 mb-2">Ajukan Klaim Akun Pelaku UMKM</h1>
                <p class="text-body-secondary mb-0">
                    Pengajuan ini hanya meminta verifikasi keterkaitan pemohon dengan record UMKM.
                    Data sumber LSS tidak diubah oleh proses klaim.
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Pengajuan belum dapat diproses.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pelaku-claim.store') }}" class="d-grid gap-3">
                @csrf

                <div>
                    <label class="form-label" for="umkm_code">Kode UMKM</label>
                    <input class="form-control @error('umkm_code') is-invalid @enderror"
                           id="umkm_code"
                           name="umkm_code"
                           value="{{ old('umkm_code') }}"
                           maxlength="100"
                           required>
                    <div class="form-text">Masukkan kode UMKM yang akan diklaim. Sistem tidak membuka daftar record publik dari halaman ini.</div>
                </div>

                <div>
                    <label class="form-label" for="applicant_name">Nama Pemohon</label>
                    <input class="form-control @error('applicant_name') is-invalid @enderror"
                           id="applicant_name"
                           name="applicant_name"
                           value="{{ old('applicant_name') }}"
                           maxlength="190"
                           required>
                </div>

                <div>
                    <label class="form-label" for="applicant_email">Email</label>
                    <input type="email"
                           class="form-control @error('applicant_email') is-invalid @enderror"
                           id="applicant_email"
                           name="applicant_email"
                           value="{{ old('applicant_email') }}"
                           maxlength="190"
                           required>
                    <div class="form-text">Email ini digunakan untuk OTP dan aktivasi setelah klaim disetujui Dinas.</div>
                </div>

                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           value="1"
                           id="ownership_declaration"
                           name="ownership_declaration"
                           @checked(old('ownership_declaration'))
                           required>
                    <label class="form-check-label" for="ownership_declaration">
                        Saya menyatakan memiliki keterkaitan sebagai pemilik UMKM dan bersedia diverifikasi oleh Dinas.
                    </label>
                </div>

                <div class="alert alert-info mb-0">
                    Dinas tidak membuat password default. Password baru dibuat sendiri oleh Pelaku setelah approval dan verifikasi OTP.
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-between">
                    <a class="btn btn-outline-secondary" href="{{ url('/') }}">Kembali</a>
                    <button class="btn btn-primary px-4" type="submit">Kirim Klaim</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection