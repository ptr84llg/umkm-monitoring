@extends('layouts.auth')

@php
    $assetProfile = 'base';
    $assetModules = ['loader', 'location', 'session'];
    $pageCss = ['auth/login.css'];
    $pageJs = ['auth/password-reset.js', 'auth-login-anti-bot.js'];
@endphp

@section('title', 'Lupa Password | Monitoring UMKM')

@section('content')
<section class="auth-login-page auth-login-premium" data-auth-password-page data-auth-landing-url="{{ url('/') }}">
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
                            <span class="auth-brand-mark">MU</span>
                            <span class="auth-brand-text">
                                <strong class="d-block">Monitoring UMKM</strong>
                                <small class="d-block">Pemulihan Akses Internal</small>
                            </span>
                        </a>

                        <div class="card border-0 shadow-sm auth-login-card">
                            <div class="card-body p-4 p-xl-5">
                                <span class="auth-card-eyebrow">Pemulihan Akun</span>
                                <h1 class="h3 fw-bold auth-card-title mt-2 mb-2">Lupa Password</h1>
                                <p class="auth-card-subtitle mb-4">
                                    Masukkan email akun. Jika akun terdaftar dan aktif, sistem akan mengirim tautan pengaturan ulang password.
                                </p>

                                @if (session('status'))
                                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger" role="alert">
                                        Permintaan belum dapat diproses. Periksa kembali data yang dikirim.
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('password.email') }}" data-auth-password-reset-form data-umkm-anti-bot-form novalidate>
                                    @csrf

                                    <div class="visually-hidden" aria-hidden="true" data-umkm-login-honeypot>
                                        <label for="password_reset_website">Website</label>
                                        <input type="text" id="password_reset_website" name="website" value="" tabindex="-1" autocomplete="off">
                                    </div>
                                    <input type="hidden" name="tts" value="0" data-umkm-login-tts>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Akun</label>
                                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" autocomplete="email" maxlength="190" required autofocus>
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-3 auth-submit" data-auth-password-reset-submit>
                                        Kirim Tautan Reset
                                    </button>

                                    <div class="rounded-4 p-3 mt-3 auth-form-note">
                                        <strong>Catatan keamanan:</strong> tautan reset hanya berlaku terbatas dan tidak menampilkan status akun secara terbuka.
                                    </div>
                                </form>

                                <div class="auth-return-row">
                                    <a href="{{ route('login') }}" class="auth-return-action" aria-label="Kembali ke halaman login">
                                        <span class="auth-action-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M10.8 5.2 4 12l6.8 6.8 1.4-1.4L7.8 13H20v-2H7.8l4.4-4.4-1.4-1.4Z"/></svg>
                                        </span>
                                        <span>Kembali ke login</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
