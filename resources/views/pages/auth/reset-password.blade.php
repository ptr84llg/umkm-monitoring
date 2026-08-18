@extends('layouts.auth')

@php
    $assetProfile = 'base';
    $assetModules = ['loader', 'location', 'session'];
    $pageCss = ['auth/login.css'];
    $pageJs = ['auth/password-reset.js', 'auth-login-anti-bot.js'];
    $linkInvalid = (bool) ($linkInvalid ?? false);
    $expiresAt = $expiresAt ?? null;
@endphp

@section('title', 'Atur Ulang Kata Sandi | Monitoring UMKM')

@section('content')
<section class="auth-login-page auth-login-premium"
         data-auth-password-page
         data-auth-landing-url="{{ url('/') }}"
         data-auth-reset-link-invalid="{{ $linkInvalid ? '1' : '0' }}"
         data-auth-reset-link-expires-at="{{ $expiresAt }}">
    <div class="auth-background" aria-hidden="true">
        <span class="auth-gradient auth-gradient-a"></span>
        <span class="auth-gradient auth-gradient-b"></span>
    </div>

    <div class="container py-4 py-xl-5 auth-container">
        <div class="card border-0 auth-shell">
            <div class="card-body p-4 p-xl-5">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-lg-6 mx-lg-auto">
                        <a href="{{ route('login') }}" class="d-inline-flex align-items-center gap-3 text-decoration-none auth-brand-link mb-4">
                            <span class="auth-brand-mark system-brand-mark" aria-hidden="true">
                                <img class="system-brand-image auth-brand-image"
                                     src="{{ asset('assets/img/brand/umkm-monitoring-icon-64.png') }}"
                                     alt=""
                                     width="44"
                                     height="44"
                                     loading="eager">
                            </span>
                            <span class="auth-brand-text">
                                <strong class="d-block">Monitoring UMKM</strong>
                                <small class="d-block">Pengaturan Ulang Kata Sandi</small>
                            </span>
                        </a>

                        <div class="card border-0 shadow-sm auth-login-card">
                            <div class="card-body p-4 p-xl-5">
                                @if ($linkInvalid)
                                    <span class="auth-card-eyebrow">Tautan Tidak Berlaku</span>
                                    <h1 class="h3 fw-bold auth-card-title mt-2 mb-2">{{ $expiredTitle ?? 'Tautan Tidak Berlaku' }}</h1>
                                    <p class="auth-card-subtitle mb-4">{{ $expiredMessage ?? 'Tautan pengaturan ulang kata sandi tidak berlaku atau sudah kedaluwarsa.' }}</p>
                                    <div class="rounded-4 p-3 mb-4 auth-form-note">
                                        <strong>Catatan keamanan:</strong> halaman pengaturan ulang hanya dapat digunakan selama tautan masih berlaku.
                                    </div>
                                    <a href="{{ route('password.request') }}" class="auth-return-action text-decoration-none justify-content-center">
                                        <span class="auth-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5V2L7 7l5 5V8c3.31 0 6 2.69 6 6a6 6 0 0 1-9.2 5.08l-1.45 1.45A8 8 0 1 0 12 5Z"/></svg></span>
                                        <span>Minta tautan baru</span>
                                    </a>
                                @else
                                    <div data-auth-reset-valid-panel>
                                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                            <div>
                                                <span class="auth-card-eyebrow">Atur Ulang Kata Sandi</span>
                                                <h1 class="h3 fw-bold auth-card-title mt-2 mb-2">Buat Kata Sandi Baru</h1>
                                                <p class="auth-card-subtitle mb-0">Gunakan kata sandi baru yang kuat. Perubahan akan dikonfirmasi dengan kode verifikasi yang dikirim melalui email.</p>
                                            </div>
                                            <span class="badge rounded-pill auth-card-badge" data-auth-reset-link-status>Tautan Aktif</span>
                                        </div>

                                        <div class="auth-otp-timer-grid mb-4" role="status" aria-live="polite">
                                            <div class="auth-otp-timer-card">
                                                <span>Tautan berlaku</span>
                                                <strong data-auth-reset-link-countdown>--:--</strong>
                                            </div>
                                            <div class="auth-otp-timer-card">
                                                <span>Berlaku sampai</span>
                                                <strong>{{ $expiresAtLabel ?? '--:--' }}</strong>
                                            </div>
                                        </div>

                                        @if ($errors->any())
                                            <div class="alert alert-danger" role="alert">
                                                Permintaan belum dapat diproses. Periksa kembali email dan kata sandi baru.
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('password.update') }}" data-auth-password-reset-form data-umkm-anti-bot-form novalidate>
                                            @csrf
                                            <input type="hidden" name="token" value="{{ $token }}">
                                            <div class="visually-hidden" aria-hidden="true" data-umkm-login-honeypot>
                                                <label for="password_update_website">Website</label>
                                                <input type="text" id="password_update_website" name="website" value="" tabindex="-1" autocomplete="off">
                                            </div>
                                            <input type="hidden" name="tts" value="0" data-umkm-login-tts>

                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email Akun</label>
                                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $email) }}" autocomplete="email" maxlength="190" required readonly>
                                                @error('email')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="password" class="form-label">Kata Sandi Baru</label>
                                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8" required>
                                                @error('password')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi Baru</label>
                                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password" minlength="8" required>
                                            </div>

                                            <button type="submit" class="btn btn-primary w-100 py-3 auth-submit" data-auth-password-reset-submit>
                                                Simpan Kata Sandi Baru
                                            </button>

                                            <div class="rounded-4 p-3 mt-3 auth-form-note">
                                                <strong>Catatan keamanan:</strong> kata sandi baru akan disimpan setelah kode verifikasi email berhasil diperiksa.
                                            </div>
                                        </form>
                                    </div>

                                    <div data-auth-reset-expired-panel hidden>
                                        <span class="auth-card-eyebrow">Tautan Kedaluwarsa</span>
                                        <h1 class="h3 fw-bold auth-card-title mt-2 mb-2">Tautan Sudah Berakhir</h1>
                                        <p class="auth-card-subtitle mb-4">Batas waktu tautan sudah habis. Demi keamanan, halaman pengaturan kata sandi tidak dapat digunakan lagi.</p>
                                        <a href="{{ route('password.request') }}" class="auth-return-action text-decoration-none justify-content-center">
                                            <span class="auth-action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5V2L7 7l5 5V8c3.31 0 6 2.69 6 6a6 6 0 0 1-9.2 5.08l-1.45 1.45A8 8 0 1 0 12 5Z"/></svg></span>
                                            <span>Minta tautan baru</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   </div>
</section>
@endsection