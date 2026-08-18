@extends('layouts.auth')

@php
    $assetProfile = 'base';
@endphp

@section('title', 'Aktivasi Akun Pelaku UMKM | SISFODA')

@section('content')
<div class="container py-5" style="max-width: 760px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <span class="badge text-bg-success mb-3">Pengajuan Disetujui Dinas</span>
            <h1 class="h3 mb-2">Aktifkan Akun Pelaku UMKM</h1>
            <p class="text-body-secondary">
                Kode OTP dikirim ke {{ $activationContext['masked_email'] }}.
                Password dibuat sendiri oleh Pelaku dan tidak diketahui oleh Dinas.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('pelaku-activation.activate', ['claim_reference' => $claim->claim_reference]) }}"
                  class="d-grid gap-3">
                @csrf
                <input type="hidden" name="activation_token" value="{{ $activationToken }}">

                <div>
                    <label class="form-label" for="otp">Kode OTP</label>
                    <input class="form-control"
                           id="otp"
                           name="otp"
                           inputmode="numeric"
                           autocomplete="one-time-code"
                           minlength="6"
                           maxlength="6"
                           required>
                </div>

                @if ($activationContext['requires_password'])
                    <div>
                        <label class="form-label" for="password">Buat Password</label>
                        <input type="password"
                               class="form-control"
                               id="password"
                               name="password"
                               autocomplete="new-password"
                               minlength="12"
                               required>
                        <div class="form-text">Minimal 12 karakter. Password dibuat langsung oleh Pelaku.</div>
                    </div>

                    <div>
                        <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                        <input type="password"
                               class="form-control"
                               id="password_confirmation"
                               name="password_confirmation"
                               autocomplete="new-password"
                               minlength="12"
                               required>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        Email ini sudah terhubung dengan akun Pelaku aktif. Password akun yang sudah ada tidak diubah.
                    </div>
                @endif

                <button class="btn btn-primary" type="submit">Verifikasi OTP dan Aktifkan</button>
            </form>
        </div>
    </div>
</div>
@endsection