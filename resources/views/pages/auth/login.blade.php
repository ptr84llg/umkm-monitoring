@extends('layouts.auth')

@php
    $assetProfile = 'base';
    $assetModules = ['loader', 'location', 'session'];
    $pageCss = ['auth/login.css'];
    $pageJs = ['auth/login.js'];
@endphp

@section('title', 'Login Internal | Monitoring UMKM')

@section('content')
<section class="auth-login-page"
         data-auth-login-page
         data-auth-landing-url="{{ url('/') }}"
         data-auth-location-max-failures="3">
    <div class="auth-background" aria-hidden="true">
        <span class="auth-gradient auth-gradient-a"></span>
        <span class="auth-gradient auth-gradient-b"></span>
    </div>

    <div class="container py-4 py-xl-5 auth-container">
        <div class="card border-0 auth-shell">
            <div class="card-body p-4 p-xl-5">
                <div class="row g-2">
                    <div class="col-12 col-lg-8 col-xl-6 mx-lg-auto">
                        <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-3 text-decoration-none auth-brand-link mb-4" aria-label="Kembali ke Beranda Monitoring UMKM">
                            <span class="auth-brand-mark">MU</span>
                            <span class="auth-brand-text">
                                <strong class="d-block">Monitoring UMKM</strong>
                                <small class="d-block">Visual Analitik Interaktif</small>
                            </span>
                        </a>

                        <div class="mb-4">
                            <span class="umkm-kicker">Akses Internal Terbatas</span>
                            <h1 class="display-5 fw-bold auth-hero-title mt-3 mb-3">
                                Masuk ke Sistem Monitoring UMKM
                            </h1>
                            <p class="lead auth-hero-text mb-0">
                                Akses ini digunakan untuk pengelolaan data, validasi, pemantauan indikator,
                                visual analitik, dan dukungan pengambilan keputusan UMKM sesuai kewenangan pengguna.
                            </p>
                        </div>

                        <div class="list-group auth-security-list mb-3">
                            <div class="list-group-item d-flex align-items-start gap-3 auth-security-item">
                                <span class="auth-security-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5.1 3.4 9.8 8 11 4.6-1.2 8-5.9 8-11V5l-8-3Zm0 2.2 6 2.25V11c0 4.05-2.45 7.85-6 8.9C8.45 18.85 6 15.05 6 11V6.45l6-2.25Zm3.7 5.95-4.5 4.5-2.1-2.1-1.4 1.4 3.5 3.5 5.9-5.9-1.4-1.4Z"/></svg>
                                </span>
                                <span>
                                    <strong class="d-block">Keamanan berlapis</strong>
                                    <small class="d-block">CSRF, validasi server, audit, pembatasan akses, dan session guard.</small>
                                </span>
                            </div>

                            <div class="list-group-item d-flex align-items-start gap-3 auth-security-item">
                                <span class="auth-security-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z"/></svg>
                                </span>
                                <span>
                                    <strong class="d-block">Pemeriksaan lokasi</strong>
                                    <small class="d-block">Form login hanya ditampilkan ketika lokasi perangkat berhasil dibaca.</small>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 auth-integrity-strip">
                            <span>CSRF</span>
                            <span>Location Gate</span>
                            <span>Audit Log</span>
                            <span>Session Guard</span>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8 col-xl-6 mx-lg-auto mt-3 mt-xl-0">
                        <div class="card border-0 shadow-sm auth-login-card">
                            <div class="card-body p-4 p-xl-5">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                    <div>
                                        <span class="auth-card-eyebrow">Login Internal</span>
                                        <h2 class="h3 fw-bold auth-card-title mt-2 mb-0">Masuk ke Akun</h2>
                                    </div>
                                    <span class="badge rounded-pill auth-card-badge">Secure</span>
                                </div>

                                <div class="auth-location-reading" data-auth-location-reading role="status" aria-live="polite">
                                    <span class="auth-location-orbit" aria-hidden="true">
                                        <span></span>
                                    </span>
                                    <div class="auth-location-reading-copy">
                                        <strong data-auth-location-reading-title>Sedang membaca lokasi perangkat</strong>
                                        <small data-auth-location-reading-message data-auth-location-text>
                                            Sistem sedang memastikan lokasi aktif sebelum form login ditampilkan.
                                        </small>
                                    </div>

                                    <div class="auth-location-reading-actions">
                                        <span class="auth-location-status visually-hidden" data-auth-location-status="checking"></span>
                                        <span class="auth-location-attempt" data-auth-location-attempt>Percobaan 1 dari 3</span>
                                        <button type="button" class="btn btn-light btn-sm auth-location-button" data-auth-location-check>
                                            Periksa Lokasi
                                        </button>
                                    </div>
                                </div>

                                <div class="auth-login-form-shell is-location-hidden"
                                     data-auth-login-form-shell
                                     aria-hidden="true">
                                    @if ($errors->any())
                                        <div class="alert alert-danger d-grid gap-1" role="alert">
                                            <strong>Login belum berhasil.</strong>
                                            <span>Periksa kembali kredensial dan kesiapan perangkat Anda.</span>
                                        </div>
                                    @endif

                                    @if (session('status'))
                                        <div class="alert alert-success" role="status">
                                            {{ session('status') }}
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('login.store') }}" data-auth-login-form novalidate>
                                        @csrf

                                        <input type="hidden" name="location_status" value="pending" data-auth-location-status-input>
                                        <input type="hidden" name="location_latitude" value="" data-auth-location-latitude-input>
                                        <input type="hidden" name="location_longitude" value="" data-auth-location-longitude-input>
                                        <input type="hidden" name="location_accuracy" value="" data-auth-location-accuracy-input>
                                        <input type="hidden" name="location_checked_at" value="" data-auth-location-checked-at-input>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <div class="input-group auth-input-group">
                                                <span class="input-group-text">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5L4 8V6l8 5 8-5v2Z"/></svg>
                                                </span>
                                                <input
                                                    type="email"
                                                    name="email"
                                                    id="email"
                                                    class="form-control @error('email') is-invalid @enderror"
                                                    value="{{ old('email') }}"
                                                    autocomplete="username"
                                                    inputmode="email"
                                                    maxlength="190"
                                                    required
                                                    autofocus
                                                >
                                            </div>
                                            @error('email')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <div class="input-group auth-input-group">
                                                <span class="input-group-text">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 8V7a5 5 0 0 0-10 0v1H5v14h14V8h-2Zm-8 0V7a3 3 0 0 1 6 0v1H9Zm4 9.73V19h-2v-1.27A2 2 0 1 1 13 17.73Z"/></svg>
                                                </span>
                                                <input
                                                    type="password"
                                                    name="password"
                                                    id="password"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    autocomplete="current-password"
                                                    minlength="8"
                                                    required
                                                    data-auth-password
                                                >
                                                <button type="button" class="btn btn-light auth-password-toggle" data-auth-password-toggle aria-label="Tampilkan password">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5 0 9 4.5 10 7-1 2.5-5 7-10 7S3 14.5 2 12c1-2.5 5-7 10-7Zm0 2C8.6 7 5.65 9.65 4.25 12 5.65 14.35 8.6 17 12 17s6.35-2.65 7.75-5C18.35 9.65 15.4 7 12 7Zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6Z"/></svg>
                                                </button>
                                            </div>
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100 py-3 auth-submit" data-auth-submit disabled>
                                            <span class="auth-submit-text">Masuk ke Sistem</span>
                                        </button>

                                        <div class="rounded-4 p-3 mt-3 auth-form-note">
                                            <strong>Catatan keamanan:</strong>
                                            jangan membagikan email, password, atau kode verifikasi kepada pihak lain.
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-center gap-2 mt-3 auth-footer-note">
            <a href="{{ url('/') }}">Kembali ke Beranda</a>
            <span>•</span>
            <span>Monitoring UMKM</span>
        </div>
    </div>
</section>
@endsection

