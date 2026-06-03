@extends('layouts.auth')

@php
    $assetProfile = 'base';
    $assetModules = ['loader', 'location', 'session'];
    $pageCss = ['auth/login.css'];
@endphp

@section('title', 'Tautkan Google | Monitoring UMKM')

@section('content')
<section class="auth-login-page auth-login-premium auth-google-link-page"
         data-auth-login-page
         data-auth-landing-url="{{ url('/') }}"
         data-auth-location-max-failures="3">
    <div class="auth-background" aria-hidden="true">
        <span class="auth-gradient auth-gradient-a"></span>
        <span class="auth-gradient auth-gradient-b"></span>
    </div>

    <div class="container py-4 py-xl-5 auth-container">
        <div class="card border-0 auth-shell auth-google-link-shell">
            <div class="card-body p-4 p-xl-5">
                <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-3 text-decoration-none auth-brand-link mb-4" aria-label="Kembali ke Beranda Monitoring UMKM">
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
                        <small class="d-block">Tautkan Akun Google</small>
                    </span>
                </a>

                <div class="card border-0 shadow-sm auth-login-card mx-auto auth-google-link-card">
                    <div class="card-body p-4 p-xl-5">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                            <div>
                                <span class="auth-card-eyebrow">Konfirmasi Google</span>
                                <h1 class="h3 fw-bold auth-card-title mt-2 mb-1">Tautkan Google ke Akun Terdaftar?</h1>
                                <p class="auth-card-subtitle mb-0">
                                    Email Google ini cocok dengan akun internal yang sudah terdaftar. Tautkan hanya jika akun Google tersebut milik Anda.
                                </p>
                            </div>
                            <span class="badge rounded-pill auth-card-badge">Aman</span>
                        </div>

                        <div class="rounded-4 p-3 border bg-light-subtle d-flex align-items-center gap-3">
                            <span class="auth-google-link-avatar" aria-hidden="true">G</span>
                            <span>
                                <strong class="d-block">{{ $pendingName }}</strong>
                                <small class="d-block text-muted">{{ $pendingEmail }}</small>
                            </span>
                        </div>

                        <div class="rounded-4 p-3 mt-3 auth-form-note">
                            <strong>Catatan keamanan:</strong>
                            setelah ditautkan, login manual untuk akun ini akan dinonaktifkan dan akses berikutnya dilakukan melalui Google.
                        </div>

                        <div class="d-grid gap-3 mt-4">
                            <form method="POST" action="{{ route('login.google.link') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100 py-3 auth-submit">
                                    Tautkan dan Masuk dengan Google
                                </button>
                            </form>

                            <form method="POST" action="{{ route('login.google.cancel') }}">
                                @csrf
                                <button type="submit" class="btn btn-light w-100 py-3">
                                    Batalkan Proses Tautkan
                                </button>
                            </form>
                        </div>

                        <div class="text-center mt-3 auth-footer-note">
                            <a href="{{ route('login') }}">Kembali ke login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection