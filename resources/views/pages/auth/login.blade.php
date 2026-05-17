@extends('layouts.auth')

@php
    $assetProfile = 'base';
    $assetModules = ['loader', 'location', 'session'];
    $pageCss = ['auth-login.css'];
    $pageJs = ['auth-login.js'];
@endphp

@section('title', 'Login Internal | Monitoring UMKM')

@section('content')
<section class="auth-login-page" data-auth-login-page>
    <div class="auth-background" aria-hidden="true">
        <span class="auth-gradient auth-gradient-a"></span>
        <span class="auth-gradient auth-gradient-b"></span>
    </div>

    <div class="container auth-container">
        <div class="auth-shell">
            <div class="auth-shell-grid">
                <div class="auth-shell-info">
                    <a href="{{ url('/') }}" class="auth-brand-link" aria-label="Kembali ke Beranda Monitoring UMKM">
                        <span class="auth-brand-mark">MU</span>
                        <span class="auth-brand-text">
                            <strong>Monitoring UMKM</strong>
                            <small>Visual Analitik Interaktif</small>
                        </span>
                    </a>

                    <div class="auth-hero-copy">
                        <span class="auth-kicker">Akses Internal Terbatas</span>
                        <h1>Masuk ke Sistem Monitoring UMKM</h1>
                        <p>
                            Akses ini digunakan untuk pengelolaan data, validasi, pemantauan indikator,
                            visual analitik, dan dukungan pengambilan keputusan UMKM sesuai kewenangan pengguna.
                        </p>
                    </div>

                    <div class="auth-security-brief" aria-label="Ringkasan keamanan login">
                        <div class="auth-security-brief-item">
                            <span class="auth-security-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5.1 3.4 9.8 8 11 4.6-1.2 8-5.9 8-11V5l-8-3Zm0 2.2 6 2.25V11c0 4.05-2.45 7.85-6 8.9C8.45 18.85 6 15.05 6 11V6.45l6-2.25Zm3.7 5.95-4.5 4.5-2.1-2.1-1.4 1.4 3.5 3.5 5.9-5.9-1.4-1.4Z"/></svg>
                            </span>
                            <span>
                                <strong>Keamanan berlapis</strong>
                                <small>CSRF, validasi server, audit, dan pembatasan akses tetap dijaga.</small>
                            </span>
                        </div>

                        <div class="auth-security-brief-item">
                            <span class="auth-security-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z"/></svg>
                            </span>
                            <span>
                                <strong>Pemeriksaan lokasi</strong>
                                <small>Form login aktif setelah perangkat memberi akses lokasi.</small>
                            </span>
                        </div>

                        <div class="auth-security-brief-item">
                            <span class="auth-security-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M4 4h16v2H4V4Zm0 4h16v12H4V8Zm2 2v8h12v-8H6Zm2 2h8v2H8v-2Zm0 3h5v2H8v-2Z"/></svg>
                            </span>
                            <span>
                                <strong>Aktivitas tercatat</strong>
                                <small>Aktivitas login berhasil dan gagal disiapkan untuk audit sistem.</small>
                            </span>
                        </div>
                    </div>

                    <div class="auth-integrity-strip" aria-label="Lapisan pengamanan">
                        <span>CSRF</span>
                        <span>Location Gate</span>
                        <span>Audit Log</span>
                        <span>Session Guard</span>
                    </div>
                </div>

                <div class="auth-shell-board">
                    <div class="auth-card" data-auth-card>
                        <div class="auth-card-header">
                            <div>
                                <span class="auth-card-eyebrow">Login Internal</span>
                                <h2>Masuk ke Akun</h2>
                            </div>
                            <span class="auth-card-badge">Secure</span>
                        </div>

                        <div class="auth-location-panel" data-auth-location-panel>
                            <div class="auth-location-status" data-auth-location-status="checking">
                                <span class="auth-location-dot" aria-hidden="true"></span>
                                <span data-auth-location-text>Memeriksa kesiapan lokasi perangkat...</span>
                            </div>
                            <button type="button" class="btn btn-sm auth-location-button" data-auth-location-check>
                                Periksa Lokasi
                            </button>
                        </div>

                        @if ($errors->any())
                            <div class="auth-alert auth-alert-danger" role="alert">
                                <strong>Login belum berhasil.</strong>
                                <span>Periksa kembali kredensial dan kesiapan perangkat Anda.</span>
                            </div>
                        @endif

                        @if (session('status'))
                            <div class="auth-alert auth-alert-success" role="status">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}" class="auth-form" data-auth-login-form novalidate>
                            @csrf

                            <input type="hidden" name="location_status" value="pending" data-auth-location-status-input>
                            <input type="hidden" name="location_latitude" value="" data-auth-location-latitude-input>
                            <input type="hidden" name="location_longitude" value="" data-auth-location-longitude-input>
                            <input type="hidden" name="location_accuracy" value="" data-auth-location-accuracy-input>
                            <input type="hidden" name="location_checked_at" value="" data-auth-location-checked-at-input>

                            <div class="auth-field">
                                <label for="email" class="form-label">Email</label>
                                <div class="auth-input-wrap">
                                    <span class="auth-input-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5L4 8V6l8 5 8-5v2Z"/></svg>
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
                                    <div class="auth-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="auth-field">
                                <label for="password" class="form-label">Password</label>
                                <div class="auth-input-wrap">
                                    <span class="auth-input-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24"><path d="M17 8V7a5 5 0 0 0-10 0v1H5v14h14V8h-2Zm-8 0V7a3 3 0 0 1 6 0v1H9Zm4 9.73V19h-2v-1.27A2 2 0 1 1 13 17.73Z"/></svg>
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
                                    <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-label="Tampilkan password">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5 0 9 4.5 10 7-1 2.5-5 7-10 7S3 14.5 2 12c1-2.5 5-7 10-7Zm0 2C8.6 7 5.65 9.65 4.25 12 5.65 14.35 8.6 17 12 17s6.35-2.65 7.75-5C18.35 9.65 15.4 7 12 7Zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6Z"/></svg>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="auth-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn auth-submit" data-auth-submit disabled>
                                <span class="auth-submit-text">Masuk ke Sistem</span>
                            </button>

                            <div class="auth-form-note">
                                <strong>Catatan keamanan:</strong>
                                jangan membagikan email, password, atau kode verifikasi kepada pihak lain.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-footer-note">
            <a href="{{ url('/') }}">Kembali ke Beranda</a>
            <span>•</span>
            <span>Monitoring UMKM</span>
        </div>
    </div>
</section>
@endsection
