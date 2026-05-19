@extends('layouts.public')

@php
    $assetProfile = 'landing';
    $assetModules = ['locationGate', 'session', 'readiness'];
    $vendorJs = [
        asset('assets/vendor/chartjs/chart.umd.min.js'),
    ];
    $pageCss = ['public/landing.css'];
    $pageJs = [
        'public/landing/landing-state.js',
        'public/landing/landing-navigation.js',
        'public/landing/landing-chart.js',
        'public/landing/landing-region.js',
        'public/landing/landing-location-bridge.js',
        'public/landing/landing-components.js',
        'public/landing/landing-boot.js',
    ];
@endphp

@section('title', 'Monitoring UMKM | Visual Analitik Interaktif')

@php
    $landingDashboardUrl = route('login');

    if (auth()->check()) {
        $landingUser = auth()->user();

        if ($landingUser?->hasRole('admin_utama')) {
            $landingDashboardUrl = route('admin-utama.dashboard');
        } elseif ($landingUser?->hasRole('admin_dinas')) {
            $landingDashboardUrl = route('admin-dinas.dashboard');
        } elseif ($landingUser?->hasRole('kepala_dinas')) {
            $landingDashboardUrl = route('kepala-dinas.dashboard');
        } elseif ($landingUser?->hasRole('pelaku_umkm')) {
            $landingDashboardUrl = route('pelaku-umkm.dashboard');
        } elseif ($landingUser?->hasRole('validator_ahli')) {
            $landingDashboardUrl = route('expert.validator.list');
        } elseif ($landingUser?->hasPermission('dashboard.view.executive')) {
            $landingDashboardUrl = route('dashboard.interactive');
        } else {
            $landingDashboardUrl = url('/');
        }
    }
@endphp

@section('content')
<x-umkm.readiness-loader
    id="landingReadinessLoader"
    title="Menyiapkan Preview UMKM"
    subtitle="Sistem sedang memeriksa kesiapan struktur landing, core, keamanan, lokasi, dan visual preview publik."
    :hide-delay="420"
    :lines="[
        [
            'key' => 'landing-structure',
            'label' => 'Struktur landing',
            'description' => 'Memeriksa struktur utama halaman landing.',
            'check' => 'selector',
            'selector' => '.umkm-landing',
            'required' => true,
        ],
        [
            'key' => 'core-system',
            'label' => 'Core sistem',
            'description' => 'Memeriksa kesiapan core UI sistem.',
            'check' => 'core',
            'required' => true,
        ],
        [
            'key' => 'ajax-core',
            'label' => 'AJAX internal',
            'description' => 'Memeriksa kesiapan request internal satu pintu.',
            'check' => 'module',
            'module' => 'ajax',
            'required' => true,
        ],
        [
            'key' => 'security-core',
            'label' => 'Modul keamanan',
            'description' => 'Memeriksa kesiapan metadata dan pengamanan request publik.',
            'check' => 'module',
            'module' => 'security',
            'required' => true,
        ],
        [
            'key' => 'location-module',
            'label' => 'Modul lokasi',
            'description' => 'Memeriksa kesiapan location gate untuk akses masuk sistem.',
            'check' => 'module',
            'module' => 'location',
            'required' => false,
        ],
        [
            'key' => 'session-module',
            'label' => 'Modul sesi',
            'description' => 'Memeriksa kesiapan monitoring sesi publik.',
            'check' => 'module',
            'module' => 'session',
            'required' => false,
        ],
        [
            'key' => 'chart-preview',
            'label' => 'Preview grafik',
            'description' => 'Memeriksa ketersediaan Chart.js untuk visual preview publik.',
            'check' => 'global',
            'global' => 'Chart',
            'required' => false,
        ],
        [
            'key' => 'region-preview',
            'label' => 'Preview wilayah',
            'description' => 'Memeriksa mount pilihan wilayah berbasis SSA.',
            'check' => 'selector',
            'selector' => '[data-region-modal-mount]',
            'required' => false,
        ],
        [
            'key' => 'landing-interaction',
            'label' => 'Interaksi landing',
            'description' => 'Memeriksa elemen navigasi dan interaksi landing.',
            'check' => 'selector',
            'selector' => '[data-landing-header]',
            'required' => true,
        ],
    ]"
/>
<div class="umkm-landing" data-location-gate-root data-login-url="{{ route('login') }}" data-dashboard-url="{{ $landingDashboardUrl }}" data-authenticated="{{ auth()->check() ? 'true' : 'false' }}" data-location-gate-verify-url="{{ route('public.location-gate.verify') }}" data-location-gate-clear-url="{{ route('public.location-gate.clear') }}" data-location-client-ip="{{ request()->ip() }}" data-location-client-user-agent="{{ request()->userAgent() ?? 'Tidak terbaca' }}">
    <div class="landing-gradient gradient-a" data-parallax="0.08"></div>
    <div class="landing-gradient gradient-b" data-parallax="0.12"></div>

    <header class="landing-header navbar navbar-expand-xl fixed-top" data-landing-header>
        <div class="container">
            <div class="row align-items-center g-2 w-100 landing-header-row">
                <div class="col-8 col-xl-4">
                    <a class="navbar-brand landing-brand d-inline-flex align-items-center gap-3 m-0" href="{{ url('/') }}" aria-label="Monitoring UMKM">
                        <span class="landing-brand-mark">MU</span>
                        <span class="landing-brand-text">
                            <strong>Monitoring UMKM</strong>
                            <small>Visual Analitik Interaktif</small>
                        </span>
                    </a>
                </div>

                <div class="col-xl-4 d-none d-xl-flex justify-content-center">
                    <nav class="landing-menu d-inline-flex align-items-center gap-2" aria-label="Menu utama">
                        <a class="btn btn-light btn-sm landing-menu-link" href="#dashboard">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg>
                            <span>Preview Dashboard</span>
                        </a>
                        <a class="btn btn-light btn-sm landing-menu-link" href="#ringkasan">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v2H5V4Zm0 5h14v2H5V9Zm0 5h10v2H5v-2Zm0 5h7v2H5v-2Z"/></svg>
                            <span>Ringkasan</span>
                        </a>
                    </nav>
                </div>

                <div class="col-4 col-xl-4">
                    <div class="landing-nav-actions d-flex align-items-center justify-content-end gap-2">
                        <button type="button"
                                class="landing-location-chip is-checking"
                                data-location-status-chip
                                data-location-status-open
                                aria-live="polite"
                                aria-label="Status lokasi: proses mengecek">
                            <span class="landing-location-chip-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/></svg>
                            </span>
                            <span class="landing-location-chip-copy">
                                <strong data-location-status-label>Proses mengecek</strong>
                                <small data-location-status-hint>Lokasi</small>
                            </span>
                        </button>

                        <span data-login-mount
                              data-login-key="header-login"
                              data-login-label="Masuk"
                              data-dashboard-label="Ruang Kerja"
                              data-login-class="btn btn-light btn-sm landing-login-btn d-none d-xl-inline-flex"></span>

                        <a class="btn btn-primary btn-sm landing-main-btn d-none d-xl-inline-flex" href="#dashboard">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 5h8v2h-8V5ZM3 4h8v8H3V4Zm2 2v4h4V6H5Zm8 4h8v2h-8v-2Zm0 5h8v2h-8v-2ZM3 14h8v6H3v-6Zm2 2v2h4v-2H5Z"/></svg>
                            <span>Jelajahi</span>
                        </a>

                        <button type="button"
                                class="btn btn-light btn-sm landing-menu-button d-inline-flex d-xl-none"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#landingMobileOffcanvas"
                                aria-controls="landingMobileOffcanvas"
                                aria-label="Buka menu">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <x-umkm.location-gate-modal />

    <div class="landing-component-shell landing-region-modal-mount"
         data-region-modal-mount
         data-umkm-component="landing-region-modal"
         data-umkm-component-url="{{ route('public.landing-components.region-modal') }}"
         data-umkm-component-load-on="readiness-hidden"
         data-umkm-component-loading-text="Memuat pilihan wilayah..."
         data-umkm-component-overlay="false"
         aria-live="polite"></div>
    <div class="offcanvas offcanvas-end mobile-canvas"
         tabindex="-1"
         id="landingMobileOffcanvas"
         aria-labelledby="landingMobileOffcanvasLabel"
         data-menu-canvas
         data-bs-backdrop="true"
         data-bs-scroll="false">
        <div class="offcanvas-header mobile-canvas-head px-3 py-3">
            <a class="landing-brand mobile-canvas-brand d-inline-flex align-items-center gap-2 text-decoration-none"
               href="{{ url('/') }}"
               data-menu-link
               aria-label="Monitoring UMKM">
                <span class="landing-brand-mark">MU</span>
                <span class="landing-brand-text">
                    <strong id="landingMobileOffcanvasLabel">Monitoring UMKM</strong>
                    <small>Visual Analitik Interaktif</small>
                </span>
            </a>

            <button type="button"
                    class="btn btn-light canvas-close"
                    data-bs-dismiss="offcanvas"
                    data-menu-close
                    aria-label="Tutup menu">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6.4 5 12.6 12.6-1.4 1.4L5 6.4 6.4 5Zm12.6 1.4L6.4 19 5 17.6 17.6 5 19 6.4Z"/></svg>
            </button>
        </div>

        <div class="offcanvas-body mobile-canvas-body p-3 d-flex flex-column gap-3">
            <div class="list-group mobile-canvas-menu d-grid gap-2">
                <a class="list-group-item list-group-item-action mobile-canvas-link d-flex align-items-center gap-3"
                   href="#dashboard"
                   data-menu-link>
                    <span class="mobile-canvas-link-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg>
                    </span>
                    <span>Preview Dashboard</span>
                </a>

                <a class="list-group-item list-group-item-action mobile-canvas-link d-flex align-items-center gap-3"
                   href="#ringkasan"
                   data-menu-link>
                    <span class="mobile-canvas-link-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v2H5V4Zm0 5h14v2H5V9Zm0 5h10v2H5v-2Zm0 5h7v2H5v-2Z"/></svg>
                    </span>
                    <span>Ringkasan</span>
                </a>

                <a class="list-group-item list-group-item-action mobile-canvas-link d-flex align-items-center gap-3"
                   href="#cta"
                   data-menu-link>
                    <span class="mobile-canvas-link-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 7v10l9 5 9-5V7l-9-5Zm0 2.3L17.8 7 12 9.7 6.2 7 12 4.3ZM5 8.6l6 2.8v7.9l-6-3.4V8.6Zm8 10.7v-7.9l6-2.8v7.3l-6 3.4Z"/></svg>
                    </span>
                    <span>Mulai</span>
                </a>

                <span data-login-mount
                      data-login-key="mobile-login"
                      data-login-label="Masuk Sistem"
                      data-dashboard-label="Buka Ruang Kerja"
                      data-login-class="btn btn-primary mobile-login-link d-flex align-items-center justify-content-center gap-2"
                      data-login-variant="mobile"
                      data-login-menu-link="true"></span>
            </div>

            <div class="alert mobile-canvas-note mt-auto mb-0" role="note">
                <strong>Akses aman</strong>
                <p class="mb-0">Tombol masuk hanya tersedia setelah perangkat memberikan izin lokasi.</p>
            </div>
        </div>
    </div>
    <div class="landing-main">
        <section class="hero-section pt-2 pt-xl-3 pb-5">
            <div class="container">
                <div class="card border-0 hero-shell">
                    <div class="card-body p-4 p-xl-5">
                        <div class="row align-items-xl-start g-4 g-xl-5">
                            <div class="col-12 col-lg-10 col-xl-6 mx-lg-auto mx-xl-0">
                                <div class="hero-copy reveal">
                                    <x-umkm.section-pill>
                                        <x-slot:icon>
                                            <svg viewBox="0 0 24 24"><path d="M12 2 3 7v10l9 5 9-5V7l-9-5Zm0 2.3L17.8 7 12 9.7 6.2 7 12 4.3ZM5 8.6l6 2.8v7.9l-6-3.4V8.6Zm8 10.7v-7.9l6-2.8v7.3l-6 3.4Z"/></svg>
                                        </x-slot:icon>
                                        Sistem informasi untuk ekosistem UMKM
                                    </x-umkm.section-pill>

                                    <h1 class="display-3 fw-bold mt-3 mb-3">Monitoring UMKM berbasis Data</h1>
                                    <p class="lead mb-0">
                                        Temukan ringkasan usaha, persebaran wilayah, status legalitas, dan perkembangan
                                        aktivitas UMKM dalam tampilan Visual Analitik Interaktif yang mudah dibaca untuk
                                        membantu pemantauan program dan pengambilan keputusan.
                                    </p>

                                    <div class="d-flex flex-wrap gap-3 mt-4 hero-actions">
                                        <span data-login-mount
                                              data-login-key="hero-login"
                                              data-login-label="Masuk ke Sistem"
                                              data-dashboard-label="Buka Ruang Kerja"
                                              data-login-class="btn btn-primary btn-lg landing-main-btn"></span>
                                        <a class="btn btn-outline-dark btn-lg landing-outline-btn" href="#dashboard">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg>
                                            <span>Lihat Preview</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-10 col-xl-6 mx-lg-auto mx-xl-0">
                                <div class="landing-component-shell landing-hero-board-shell"
                                     data-umkm-component="landing-hero-preview-board"
                                     data-umkm-component-url="{{ route('public.landing-components.hero-preview-board') }}"
                                     data-umkm-component-load-on="readiness-hidden"
                                     data-umkm-component-loading-text="Memuat preview dashboard publik..."
                                     aria-live="polite">
                                    <div class="card border-0 board-window landing-component-skeleton">
                                        <div class="card-body p-4">
                                            <div class="umkm-inline-loader">
                                                <span class="umkm-inline-spinner" aria-hidden="true"></span>
                                                <span class="umkm-inline-loader-text">Menyiapkan preview dashboard publik...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="dashboard"
         class="dashboard-section py-5 landing-component-shell"
         data-umkm-component="landing-dashboard-preview"
         data-umkm-component-url="{{ route('public.landing-components.dashboard-preview') }}"
         data-umkm-component-load-on="readiness-hidden"
         data-umkm-component-loading-text="Memuat preview dashboard publik..."
         aria-live="polite">
    <div class="container">
        <div class="card border-0 landing-component-skeleton">
            <div class="card-body p-4 p-xl-5">
                <div class="umkm-inline-loader">
                    <span class="umkm-inline-spinner" aria-hidden="true"></span>
                    <span class="umkm-inline-loader-text">Memuat preview dashboard publik...</span>
                </div>
            </div>
        </div>
    </div>
</section>

        <section id="ringkasan"
         class="summary-section py-5 landing-component-shell"
         data-umkm-component="landing-summary-section"
         data-umkm-component-url="{{ route('public.landing-components.summary-section') }}"
         data-umkm-component-load-on="readiness-hidden"
         data-umkm-component-loading-text="Memuat ringkasan sistem..."
         aria-live="polite">
    <div class="container">
        <div class="card border-0 landing-component-skeleton">
            <div class="card-body p-4 p-xl-5">
                <div class="umkm-inline-loader">
                    <span class="umkm-inline-spinner" aria-hidden="true"></span>
                    <span class="umkm-inline-loader-text">Memuat ringkasan sistem...</span>
                </div>
            </div>
        </div>
    </div>
</section>

        <section id="cta"
         class="cta-section py-5 landing-component-shell"
         data-umkm-component="landing-cta-section"
         data-umkm-component-url="{{ route('public.landing-components.cta-section') }}"
         data-umkm-component-load-on="readiness-hidden"
         data-umkm-component-loading-text="Memuat akses masuk sistem..."
         aria-live="polite">
    <div class="container">
        <div class="card border-0 landing-component-skeleton">
            <div class="card-body p-4 p-xl-5">
                <div class="umkm-inline-loader">
                    <span class="umkm-inline-spinner" aria-hidden="true"></span>
                    <span class="umkm-inline-loader-text">Memuat akses masuk sistem...</span>
                </div>
            </div>
        </div>
    </div>
</section>
    </div>
    <button type="button" class="to-top-button" data-to-top aria-label="Kembali ke atas">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 7 7-1.4 1.4L13 7.8V20h-2V7.8l-4.6 4.6L5 11l7-7Z"/></svg>
    </button>
</div>
@endsection



