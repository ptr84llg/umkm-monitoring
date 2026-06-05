@extends('layouts.public')

@php
    $showPublicFooter = false;
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

@section('title', 'SISFODA UMKM Visual Analitik Interaktif | Kota Lubuklinggau')

@php
    $landingDashboardUrl = route('login');
    $googlePublicIdentity = session('auth.google.public_identity');
    $hasGooglePublicIdentity = is_array($googlePublicIdentity) && ! empty($googlePublicIdentity['identity_id']);
    $googlePublicName = trim((string) ($googlePublicIdentity['provider_name'] ?? 'Akun Google'));
    $googlePublicEmail = (string) ($googlePublicIdentity['provider_email'] ?? '');
    $googlePublicMaskedEmail = 'akses publik terbatas';

    if (str_contains($googlePublicEmail, '@')) {
        [$googlePublicEmailName, $googlePublicEmailDomain] = explode('@', $googlePublicEmail, 2);
        $googlePublicMaskedEmail = substr($googlePublicEmailName, 0, 1).str_repeat('*', max(3, strlen($googlePublicEmailName) - 1)).'@'.$googlePublicEmailDomain;
    }

    $landingHeroLogo = file_exists(public_path('assets/img/brand/umkm-monitoring-icon-512.png'))
        ? asset('assets/img/brand/umkm-monitoring-icon-512.png')
        : asset('assets/img/brand/umkm-monitoring-icon-64.png');

    $publicLandingSummary = $publicLandingSummary ?? \App\Support\PublicLanding\PublicLandingData::summary();
    $publicLandingHeroCards = $publicLandingHeroCards ?? \App\Support\PublicLanding\PublicLandingData::heroCards();
    $publicLandingFooterMetrics = $publicLandingFooterMetrics ?? \App\Support\PublicLanding\PublicLandingData::footerMetrics();

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
<x-umkm.loader.readiness-loader
    id="landingReadinessLoader"
    title="Menyiapkan Portal Visual UMKM"
    subtitle="Sistem sedang memeriksa kesiapan landing, keamanan, lokasi, dan preview analitik publik."
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

<div class="umkm-landing public-analytics-portal"
     data-location-gate-root
     data-login-url="{{ route('login') }}"
     data-dashboard-url="{{ $landingDashboardUrl }}"
     data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
     data-google-public-limited="{{ ($hasGooglePublicIdentity && ! auth()->check()) ? 'true' : 'false' }}"
     data-google-public-name="{{ e($googlePublicName) }}"
     data-google-public-email="{{ e($googlePublicMaskedEmail) }}"
     data-location-gate-verify-url="{{ route('public.location-gate.verify') }}"
     data-location-gate-clear-url="{{ route('public.location-gate.clear') }}"
     data-location-client-ip="{{ request()->ip() }}"
     data-location-client-user-agent="{{ request()->userAgent() ?? 'Tidak terbaca' }}">
    <div class="landing-gradient gradient-a" data-parallax="0.08"></div>
    <div class="landing-gradient gradient-b" data-parallax="0.12"></div>

    <header class="landing-header navbar navbar-expand-xl fixed-top" data-landing-header>
        <div class="container-fluid px-3 px-lg-4">
            <div class="row align-items-center g-2 w-100 landing-header-row">
                <div class="col-8 col-xl-4">
                    <a class="navbar-brand landing-brand d-inline-flex align-items-center gap-3 m-0" href="{{ url('/') }}" aria-label="SISFODA UMKM Visual Analitik Interaktif">
                        <span class="landing-brand-mark system-brand-mark" aria-hidden="true">
                            <img class="system-brand-image landing-brand-image"
                                 src="{{ asset('assets/img/brand/umkm-monitoring-icon-64.png') }}"
                                 alt=""
                                 width="44"
                                 height="44"
                                 loading="eager">
                        </span>
                        <span class="landing-brand-text">
                            <strong>SISFODA UMKM</strong>
                            <small>Visual Analitik Interaktif</small>
                        </span>
                    </a>
                </div>

                <div class="col-xl-4 d-none d-xl-flex justify-content-center">
                    <nav class="landing-menu d-inline-flex align-items-center gap-2" aria-label="Menu utama">
                        <a class="btn btn-light btn-sm landing-menu-link is-active" href="#beranda">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 3 10.5V21h6v-6h6v6h6V10.5L12 3Z"/></svg>
                            <span>Beranda</span>
                        </a>
                        <a class="btn btn-light btn-sm landing-menu-link" href="#peta-umkm">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6-2-6 2v15l6-2 6 2 6-2V4l-6 2Zm-1 12.6-4-1.3V5.4l4 1.3v11.9Z"/></svg>
                            <span>Peta Sebaran</span>
                        </a>
                        <a class="btn btn-light btn-sm landing-menu-link" href="#statistik">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/></svg>
                            <span>Statistik</span>
                        </a>
                    </nav>
                </div>

                <div class="col-4 col-xl-4">
                    <div class="landing-nav-actions d-flex align-items-center justify-content-end gap-2">
                        @if ($hasGooglePublicIdentity)
                            <button type="button"
                                    class="landing-google-public-bell"
                                    data-google-public-info-open
                                    data-bs-toggle="modal"
                                    data-bs-target="#landingGooglePublicModal"
                                    aria-controls="landingGooglePublicModal"
                                    aria-expanded="false"
                                    title="Akses publik Google aktif. Klik untuk melihat keterangan.">
                                <span class="landing-google-public-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M12 22a2.8 2.8 0 0 0 2.68-2h-5.36A2.8 2.8 0 0 0 12 22Zm8-5h-1.5V11a6.51 6.51 0 0 0-5-6.32V3a1.5 1.5 0 0 0-3 0v1.68A6.51 6.51 0 0 0 5.5 11v6H4v2h16v-2Zm-3.5 0h-9V11a4.5 4.5 0 1 1 9 0v6Z"/></svg>
                                </span>
                                <span class="landing-google-public-dot" aria-hidden="true"></span>
                            </button>
                        @endif

                        <button type="button"
                                class="landing-location-chip is-checking d-none d-lg-inline-flex"
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
                              data-login-label="Login"
                              data-dashboard-label="Ruang Kerja"
                              data-login-class="btn btn-primary btn-sm landing-login-btn d-none d-xl-inline-flex"></span>

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

    <x-umkm.feedback.location-gate-modal />

    @if ($hasGooglePublicIdentity)
        <div class="modal fade landing-google-public-modal"
             id="landingGooglePublicModal"
             tabindex="-1"
             aria-labelledby="landingGooglePublicModalTitle"
             aria-hidden="true"
             data-google-public-info-modal>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header border-0 pb-0">
                        <div class="landing-google-public-modal-title">
                            <span class="landing-google-public-modal-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 22a2.8 2.8 0 0 0 2.68-2h-5.36A2.8 2.8 0 0 0 12 22Zm8-5h-1.5V11a6.51 6.51 0 0 0-5-6.32V3a1.5 1.5 0 0 0-3 0v1.68A6.51 6.51 0 0 0 5.5 11v6H4v2h16v-2Zm-3.5 0h-9V11a4.5 4.5 0 1 1 9 0v6Z"/></svg>
                            </span>
                            <div>
                                <p class="mb-1">Akses publik Google aktif</p>
                                <h2 class="modal-title" id="landingGooglePublicModalTitle">Akun belum tertaut ke pengguna internal</h2>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="landing-google-public-modal-summary">
                            <strong>{{ $googlePublicName }}</strong>
                            <span>{{ $googlePublicMaskedEmail }}</span>
                        </div>
                        <p class="mb-0">
                            Akun Google ini hanya membuka akses publik terbatas pada halaman landing. Dashboard internal,
                            ruang kerja, dan fitur pengelolaan data belum tersedia karena akun Google tersebut belum
                            tertaut dengan akun internal sistem.
                        </p>
                        <div class="landing-google-public-modal-note" role="note">
                            Untuk masuk ke ruang kerja, gunakan akun internal yang sudah terdaftar atau minta admin
                            menautkan akun Google dengan pengguna internal yang sesuai.
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Saya mengerti</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
               aria-label="SISFODA UMKM Visual Analitik Interaktif">
                <span class="landing-brand-mark system-brand-mark" aria-hidden="true">
                    <img class="system-brand-image landing-brand-image"
                         src="{{ asset('assets/img/brand/umkm-monitoring-icon-64.png') }}"
                         alt=""
                         width="44"
                         height="44"
                         loading="eager">
                </span>
                <span class="landing-brand-text">
                    <strong id="landingMobileOffcanvasLabel">SISFODA UMKM</strong>
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
                <a class="list-group-item list-group-item-action mobile-canvas-link d-flex align-items-center gap-3" href="#beranda" data-menu-link>
                    <span class="mobile-canvas-link-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg></span>
                    <span>Beranda</span>
                </a>
                <a class="list-group-item list-group-item-action mobile-canvas-link d-flex align-items-center gap-3" href="#peta-umkm" data-menu-link>
                    <span class="mobile-canvas-link-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/></svg></span>
                    <span>Peta Sebaran</span>
                </a>
                <a class="list-group-item list-group-item-action mobile-canvas-link d-flex align-items-center gap-3" href="#statistik" data-menu-link>
                    <span class="mobile-canvas-link-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/></svg></span>
                    <span>Statistik</span>
                </a>

                <span data-login-mount
                      data-login-key="mobile-login"
                      data-login-label="Login"
                      data-dashboard-label="Buka Ruang Kerja"
                      data-login-class="btn btn-primary mobile-login-link d-flex align-items-center justify-content-center gap-2"
                      data-login-variant="mobile"
                      data-login-menu-link="true"></span>
            </div>

            <div class="alert mobile-canvas-note mt-auto mb-0" role="note">
                <strong>Akses aman</strong>
                <p class="mb-0">Tombol login hanya tersedia setelah perangkat memberikan izin lokasi.</p>
            </div>
        </div>
    </div>

    <main class="landing-main" id="beranda">
        <section class="hero-section public-analytics-hero pt-2 pt-xl-3 pb-5">
            <div class="container-fluid px-3 px-lg-4">
                <div class="row align-items-start g-4 g-xxl-5">
                    <div class="col-12 col-xl-7">
                        <div class="hero-identity-area reveal">
                            <div class="hero-brand-cluster">
								<div class="hero-logo-stage">
                                    <img src="{{ $landingHeroLogo }}"
                                         alt=""
                                         width="512"
                                         height="512"
                                         loading="eager">
                                </div>
								<div class="hero-brand-copy">
									<span class="landing-eyebrow hero-eyebrow">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 20 6.5v7.2c0 4-3.1 7.2-8 8.3-4.9-1.1-8-4.3-8-8.3V6.5L12 2Zm0 2.3L6 7.7v6c0 2.9 2.2 5.4 6 6.3 3.8-.9 6-3.4 6-6.3v-6l-6-3.4Z"/></svg>
                                        <span>Sistem Informasi berbasis Data</span>
                                    </span>
                                    <h1 class="display-3 fw-bold mt-2 mb-3">SISFODA UMKM</h1>
								</div>
                            </div>
							<p class="lead mb-0">
								SISFODA UMKM merupakan portal visual analitik berbasis data yang menyajikan informasi sebaran, kategori, dan perkembangan UMKM secara agregat. Informasi ditampilkan secara ringkas, terstruktur, dan public-safe untuk mendukung pemantauan potensi usaha daerah serta pengambilan keputusan berbasis data.
							</p>
							<div class="d-flex flex-wrap gap-3 mt-4 hero-action-row">
								<button type="button" class="btn btn-danger btn-lg landing-main-btn" data-region-open data-region-modal-open>
									<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/></svg>
									<span>Pilih Wilayah Preview</span>
								</button>
							</div>
							
							<div class="card border-0 hero-insight-card my-4">
								<div class="card-body">
									<div>
										<span>Cakupan</span>
										<strong data-public-coverage-label>{{ $publicLandingSummary['coverage_label'] ?? 'Kota Lubuklinggau' }}</strong>
									</div>
									<div class="col ms-auto">
										<span>Data diperbarui</span>
										<strong>{{ $publicLandingSummary['updated_at_label'] ?? 'Belum tersedia' }}</strong>
									</div>
								</div>
							</div>
							
							<div class="d-flex flex-wrap gap-3 mt-4 hero-action-row">
								<a class="btn btn-outline-light btn-lg landing-outline-btn" href="#peta-umkm">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6-2-6 2v15l6-2 6 2 6-2V4l-6 2Zm-1 12.6-4-1.3V5.4l4 1.3v11.9Z"/></svg>
									<span>Lihat Peta Sebaran</span>
								</a>
								<span data-login-mount
									  data-login-key="hero-login"
									  data-login-label="Login"
									  data-dashboard-label="Ruang Kerja"
									  data-login-class="btn btn-primary btn-lg landing-main-btn"></span>
							</div>
                        </div>

					</div>

                    <div class="col-12 col-xl-5">

                        <div class="hero-aggregate-area reveal reveal-delay-1">
                            <div class="row g-3 hero-stat-row">
                                @foreach ($publicLandingHeroCards as $card)
                                    <div class="col-12 col-lg-6">
                                        <article class="card h-100 border-0 hero-stat-card hero-analytic-card">
                                            <div class="card-body">
                                                <div class="hero-stat-top">
                                                    <span class="hero-stat-icon {{ $card['icon_class'] ?? '' }}" aria-hidden="true">
                                                        <svg viewBox="0 0 24 24"><path d="{{ $card['icon_path'] ?? '' }}"/></svg>
                                                    </span>
                                                    <span class="hero-stat-chip">{{ $card['chip'] ?? 'Public-safe' }}</span>
                                                </div>
                                                <small>{{ $card['label'] ?? 'Ringkasan' }}</small>
                                                <strong>{{ $card['value'] ?? '—' }}</strong>
                                                <span>{{ $card['context'] ?? '' }}</span>
                                                <div class="stat-progress"><i class="stat-progress-fill {{ $card['progress_class'] ?? 'w-0' }}"></i></div>
                                                <div class="stat-card-foot">
                                                    <span>{{ $card['foot_label'] ?? 'Data agregat' }}</span>
                                                    <b>{{ $card['foot_value'] ?? '' }}</b>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="peta-umkm" class="map-section py-5">
            <div class="container-fluid px-3 px-lg-4">
                <div class="row align-items-end g-3 mb-4">
                    <div class="col-12 col-xl-7">
                        <span class="landing-eyebrow">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6-2-6 2v15l6-2 6 2 6-2V4l-6 2Zm-1 12.6-4-1.3V5.4l4 1.3v11.9Z"/></svg>
                            <span>Peta Sebaran</span>
                        </span>
                        <h2 class="display-6 fw-bold mt-2 mb-2">Peta Sebaran UMKM</h2>
                        <p class="lead mb-0">Peta menampilkan sebaran publik dalam bentuk agregat, tanpa membuka data sensitif.</p>
                    </div>
                    
                </div>

                <div class="landing-component-shell landing-hero-board-shell"
                     data-umkm-component="landing-hero-preview-board"
                     data-umkm-component-url="{{ route('public.landing-components.hero-preview-board') }}"
                     data-umkm-component-load-on="readiness-hidden"
                     data-umkm-component-loading-text="Memuat peta dashboard publik..."
                     aria-live="polite">
                    <div class="card border-0 board-window landing-component-skeleton">
                        <div class="card-body p-4">
                            <div class="umkm-inline-loader">
                                <span class="umkm-inline-spinner" aria-hidden="true"></span>
                                <span class="umkm-inline-loader-text">Menyiapkan peta dashboard publik...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="statistik"
                 class="dashboard-section py-5 landing-component-shell"
                 data-umkm-component="landing-dashboard-preview"
                 data-umkm-component-url="{{ route('public.landing-components.dashboard-preview') }}"
                 data-umkm-component-load-on="readiness-hidden"
                 data-umkm-component-loading-text="Memuat pusat analitik publik..."
                 aria-live="polite">
            <div class="container-fluid px-3 px-lg-4">
                <div class="card border-0 landing-component-skeleton">
                    <div class="card-body p-4 p-xl-5">
                        <div class="umkm-inline-loader">
                            <span class="umkm-inline-spinner" aria-hidden="true"></span>
                            <span class="umkm-inline-loader-text">Memuat pusat analitik publik...</span>
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
                 data-umkm-component-loading-text="Memuat akses pendaftaran dan login..."
                 aria-live="polite">
            <div class="container-fluid px-3 px-lg-4">
                <div class="card border-0 landing-component-skeleton">
                    <div class="card-body p-4 p-xl-5">
                        <div class="umkm-inline-loader">
                            <span class="umkm-inline-spinner" aria-hidden="true"></span>
                            <span class="umkm-inline-loader-text">Memuat akses pendaftaran dan login...</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <footer class="landing-footer landing-footer-proper py-5">
            <div class="container-fluid px-3 px-lg-4">
                <div class="card border-0 landing-footer-shell">
                    <div class="card-body p-4 p-xl-5">
                        <div class="row g-4 g-xl-5">
                            <div class="col-12 col-xl-4">
                                <div class="landing-footer-brand-panel h-100">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <span class="landing-footer-logo" aria-hidden="true">
                                            <img src="{{ asset('assets/img/brand/umkm-monitoring-icon-64.png') }}"
                                                 alt=""
                                                 width="48"
                                                 height="48"
                                                 loading="lazy">
                                        </span>
                                        <div>
                                            <strong>SISFODA UMKM</strong>
                                            <small>Visual Analitik Interaktif</small>
                                        </div>
                                    </div>
                                    <p class="mb-0">
                                        Portal visual analitik publik untuk membaca sebaran, tren, dan ringkasan UMKM
                                        secara agregat, informatif, aman, dan public-safe.
                                    </p>
                                    <div class="landing-footer-badges">
                                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4V5Zm2 2v10h12V7H6Zm2 8h2v-4H8v4Zm3 0h2V9h-2v6Zm3 0h2v-2h-2v2Z"/></svg>Agregat</span>
                                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 20 6.5v7.2c0 4-3.1 7.2-8 8.3-4.9-1.1-8-4.3-8-8.3V6.5L12 2Z"/></svg>Public-safe</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-xl-2">
                                <div class="landing-footer-column footer-link-panel">
                                    <h3>Navigasi</h3>
                                    <a href="#beranda"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 3 10.5V21h6v-6h6v6h6V10.5L12 3Z"/></svg><span>Beranda</span></a>
                                    <a href="#peta-umkm"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6-2-6 2v15l6-2 6 2 6-2V4l-6 2Z"/></svg><span>Peta Sebaran</span></a>
                                    <a href="#statistik"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/></svg><span>Statistik</span></a>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-xl-2">
                                <div class="landing-footer-column footer-link-panel">
                                    <h3>Akses</h3>
                                    <a href="#cta"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 9a8 8 0 0 1 16 0H4Zm15.5-8 1.5 1.5-4.5 4.5-2.5-2.5 1.5-1.5 1 1 3-3Z"/></svg><span>Aktivasi Akun UMKM</span></a>
                                    <a href="#peta-umkm"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Z"/></svg><span>Preview Wilayah</span></a>
                                    <a href="#statistik"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14v18H5V3Zm3 4h8V5H8v2Zm0 4h8V9H8v2Zm0 4h5v-2H8v2Z"/></svg><span>Ringkasan Data</span></a>
                                </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <div class="landing-footer-info-panel footer-coverage-panel">
                                    <h3>Cakupan Portal</h3>
                                    <p class="mb-0">
                                        Portal menampilkan peta sebaran, statistik agregat, komposisi skala usaha,
                                        tren pertumbuhan, dan ringkasan wilayah dalam mode public-safe untuk mendukung
                                        literasi data UMKM.
                                    </p>
                                    <div class="footer-coverage-metrics">
                                        @foreach ($publicLandingFooterMetrics as $metric)
                                            <span><b>{{ $metric['value'] ?? '—' }}</b><small>{{ $metric['label'] ?? 'Data agregat' }}</small></span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="landing-footer-bottom d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2 mt-3">
                    <span>© 2026 SISFODA UMKM.</span>
                    <span>Visual Analitik Interaktif</span>
                </div>
            </div>
        </footer>
    </main>

    <button type="button" class="to-top-button" data-to-top aria-label="Kembali ke atas">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 7 7-1.4 1.4L13 7.8V20h-2V7.8l-4.6 4.6L5 11l7-7Z"/></svg>
    </button>
</div>
@endsection

