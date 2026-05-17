@extends('layouts.public')

@php
    $assetProfile = 'landing';
    $assetModules = ['location', 'session', 'readiness'];
    $vendorJs = [
        asset('assets/vendor/chartjs/chart.umd.min.js'),
    ];
    $pageCss = ['public/landing.css'];
    $pageJs = ['public/landing.js'];
@endphp

@section('title', 'Monitoring UMKM | Visual Analitik Interaktif')

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
            'description' => 'Memeriksa elemen pilihan wilayah preview.',
            'check' => 'selector',
            'selector' => '[data-region-modal]',
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
<div class="umkm-landing" data-login-url="{{ route('login') }}" data-location-client-ip="{{ request()->ip() }}" data-location-client-user-agent="{{ request()->userAgent() ?? 'Tidak terbaca' }}">
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
    <div class="modal fade landing-region-modal-shell"
         id="landingRegionModal"
         tabindex="-1"
         aria-labelledby="landingRegionModalTitle"
         aria-hidden="true"
         data-region-modal>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable landing-region-dialog">
            <section class="modal-content landing-region-modal">
                <div class="modal-header landing-region-modal-head">
                    <span class="region-modal-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/>
                        </svg>
                    </span>

                    <div class="region-modal-copy">
                        <h5 class="modal-title" id="landingRegionModalTitle">Pilih Wilayah Preview</h5>
                        <p class="mb-0">
                            Wilayah pada landing dikunci untuk Sumatera Selatan dan Kota Lubuklinggau.
                            Data yang tampil bersifat agregat/preview dan tidak menampilkan data sensitif.
                        </p>
                    </div>

                    <button type="button"
                            class="btn-close landing-region-modal-close"
                            data-bs-dismiss="modal"
                            data-region-modal-close
                            aria-label="Tutup pilihan wilayah"></button>
                </div>

                <div class="modal-body landing-region-modal-body">
                    <div class="alert landing-region-alert" role="alert" data-region-modal-alert hidden></div>

                    <div class="row g-3 landing-region-form">
                        <div class="col-12 col-md-6 landing-region-field">
                            <label for="landingProvinceSelect" class="form-label">Provinsi</label>
                            <select id="landingProvinceSelect" class="form-select" data-landing-region-province disabled>
                                <option value="16">Sumatera Selatan</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 landing-region-field">
                            <label for="landingCitySelect" class="form-label">Kabupaten/Kota</label>
                            <select id="landingCitySelect" class="form-select" data-landing-region-city disabled>
                                <option value="16.73">Kota Lubuklinggau</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 landing-region-field">
                            <label for="landingDistrictSelect" class="form-label">Kecamatan</label>
                            <select id="landingDistrictSelect" class="form-select" data-landing-region-district>
                                <option value="">Memuat kecamatan...</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 landing-region-field">
                            <label for="landingVillageSelect" class="form-label">Desa/Kelurahan</label>
                            <select id="landingVillageSelect" class="form-select" data-landing-region-village>
                                <option value="__ALL_VILLAGES__">Semua Kelurahan</option>
                            </select>
                        </div>
                    </div>

                    <div class="card border-0 landing-region-current">
                        <div class="card-body">
                            <span>Konteks saat ini</span>
                            <strong data-region-modal-current>Kota Lubuklinggau</strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer landing-region-modal-actions">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-region-modal-close>Batal</button>
                    <button type="button" class="btn btn-primary" data-region-modal-apply>Terapkan Wilayah</button>
                </div>
            </section>
        </div>
    </div>
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
                                              data-login-class="btn btn-primary btn-lg landing-main-btn"></span>
                                        <a class="btn btn-outline-dark btn-lg landing-outline-btn" href="#dashboard">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg>
                                            <span>Lihat Preview</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-10 col-xl-6 mx-lg-auto mx-xl-0">
                                <div class="hero-board reveal reveal-delay-1" data-tilt-card>
                                    <div class="card border-0 board-source board-source-stacked mb-3" data-public-region-current>
                                        <div class="card-body p-3">
                                            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-3">
                                                <div class="board-source-info">
                                                    <span class="board-source-kicker">Wilayah aktif</span>
                                                    <strong data-public-region-source>Kota Lubuklinggau</strong>
                                                </div>

                                                <button type="button" class="btn btn-primary board-region-button" data-region-modal-open>
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/>
                                                    </svg>
                                                    <span>Pilih Wilayah</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card border-0 board-window">
                                        <div class="board-top">
                                            <div class="board-dots">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </div>
                                            <strong>Preview Dashboard UMKM</strong>
                                            <small>preview agregat</small>
                                        </div>

                                        <div class="card-body p-0">
                                            <div class="row g-3 board-metrics">
                                                <div class="col-12 col-sm-4">
                                                    <div class="card border-0 h-100">
                                                        <div class="card-body">
                                                            <span>UMKM Terdata</span>
                                                            <strong class="count-up" data-count="1248" data-public-metric="total">0</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-4">
                                                    <div class="card border-0 h-100">
                                                        <div class="card-body">
                                                            <span>UMKM Aktif</span>
                                                            <strong class="count-up" data-count="1086" data-public-metric="active">0</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-4">
                                                    <div class="card border-0 h-100">
                                                        <div class="card-body">
                                                            <span>Perlu Validasi</span>
                                                            <strong class="count-up" data-count="36" data-public-metric="validation">0</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <x-umkm.public-note title="Catatan:" class="mx-3 my-3">
                                                Angka dan grafik merupakan hasil agregat data wilayah terpilih. Data rinci hanya tersedia bagi pengguna berizin.
                                            </x-umkm.public-note>

                                            <div class="row g-3 board-preview-grid">
                                                <div class="col-12 col-md-6">
                                                    <div class="card h-100 preview-map-card">
                                                        <div class="card-body">
                                                            <div class="preview-card-title">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v13A2.5 2.5 0 0 1 17.5 21h-11A2.5 2.5 0 0 1 4 18.5v-13Zm3 1v3h3v-3H7Zm5 0v3h5v-3h-5Zm-5 5v3h3v-3H7Zm5 0v3h5v-3h-5Zm-5 5v2h3v-2H7Zm5 0v2h5v-2h-5Z"/></svg>
                                                                <strong>Data Wilayah</strong>
                                                            </div>
                                                            <div class="preview-region-stats" data-public-area-list>
                                                                <div>
                                                                    <span>Lubuk Linggau Timur II</span>
                                                                    <strong>312 UMKM</strong>
                                                                    <small>Perdagangan 42%</small>
                                                                </div>
                                                                <div>
                                                                    <span>Lubuk Linggau Utara II</span>
                                                                    <strong>286 UMKM</strong>
                                                                    <small>Kuliner 35%</small>
                                                                </div>
                                                                <div>
                                                                    <span>Lubuk Linggau Barat II</span>
                                                                    <strong>214 UMKM</strong>
                                                                    <small>Jasa 23%</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="card h-100 preview-list-card">
                                                        <div class="card-body">
                                                            <div class="preview-card-title">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/></svg>
                                                                <strong>Indikator</strong>
                                                            </div>
                                                            <div class="preview-progress" data-public-field-list>
                                                                <div>
                                                                    <span>Perdagangan</span>
                                                                    <b class="progress-fill-82"></b>
                                                                </div>
                                                                <div>
                                                                    <span>Kuliner</span>
                                                                    <b class="progress-fill-74"></b>
                                                                </div>
                                                                <div>
                                                                    <span>Jasa</span>
                                                                    <b class="progress-fill-64"></b>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <x-umkm.empty-state class="landing-empty-state" icon-class="landing-empty-icon" data-public-empty-state hidden>
                                                        <x-slot:icon>
                                                            <svg viewBox="0 0 24 24">
                                                                <path d="M12 2.75A9.25 9.25 0 1 0 21.25 12 9.26 9.26 0 0 0 12 2.75Zm0 16.5A7.25 7.25 0 1 1 19.25 12 7.26 7.26 0 0 1 12 19.25Zm-1-11h2v5.5h-2V8.25Zm0 7h2v2h-2v-2Z"/>
                                                            </svg>
                                                        </x-slot:icon>
                                                        <strong data-public-empty-title>Data wilayah belum tersedia</strong>
                                                        <p data-public-empty-message>Belum ada data agregat UMKM untuk wilayah yang dipilih. Pilih wilayah lain atau kembali ke Kota Lubuklinggau untuk melihat preview agregat.</p>
                                                    </x-umkm.empty-state>
                                                </div>
                                            </div>

                                            <div class="row g-3 board-bottom">
                                                <div class="col-12 col-sm-6">
                                                    <div class="card border-0 h-100">
                                                        <div class="card-body d-flex align-items-center gap-3">
                                                            <span class="status-icon">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm3 5h8V6H8v2Zm0 5h8v-2H8v2Zm0 5h5v-2H8v2Z"/>
                                                                </svg>
                                                            </span>
                                                            <span>
                                                                <strong>Wilayah Terpantau</strong>
                                                                <small data-public-watched-label>8 Kecamatan</small>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <div class="card border-0 h-100">
                                                        <div class="card-body d-flex align-items-center gap-3">
                                                            <span class="status-icon gold">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path d="M12 2 3 7v10l9 5 9-5V7l-9-5Zm0 2.3L17.8 7 12 9.7 6.2 7 12 4.3ZM5 8.6l6 2.8v7.9l-6-3.4V8.6Zm8 10.7v-7.9l6-2.8v7.3l-6 3.4Z"/>
                                                                </svg>
                                                            </span>
                                                            <span>
                                                                <strong>Bidang Dominan</strong>
                                                                <small data-public-dominant-label>Perdagangan</small>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
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

        <section id="dashboard" class="dashboard-section py-5">
            <div class="container">
                <div class="card border-0 dashboard-panel reveal">
                    <div class="card-body p-4 p-xl-5">
                        <div class="row align-items-xl-end g-4 mb-4">
                            <div class="col-12 col-lg-10 col-xl-7 mx-lg-auto mx-xl-0">
                                <x-umkm.section-pill>
                                    <x-slot:icon>
                                        <svg viewBox="0 0 24 24"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg>
                                    </x-slot:icon>
                                    Preview dashboard publik
                                </x-umkm.section-pill>

                                <h2 class="display-6 fw-bold mt-3 mb-3">
                                    Preview informasi UMKM tampil dalam visual yang lebih hidup
                                </h2>

                                <p class="lead mb-0">
                                    Dashboard membantu membaca kondisi UMKM berdasarkan indikator utama, bidang usaha,
                                    perkembangan data, dan sebaran wilayah dalam tampilan yang lebih ringkas.
                                </p>
                            </div>

                            <div class="col-12 col-lg-10 col-xl-4 ms-xl-auto mx-lg-auto mx-xl-0">
                                <div class="row g-3 dashboard-insight">
                                    <div class="col-12 col-sm-6 col-xl-12">
                                        <div class="card h-100 border-0 dashboard-insight-card">
                                            <div class="card-body d-flex align-items-center gap-3">
                                                <span class="dashboard-insight-icon">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/>
                                                    </svg>
                                                </span>
                                                <span>Visual Analitik Interaktif</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-6 col-xl-12">
                                        <div class="card h-100 border-0 dashboard-insight-card">
                                            <div class="card-body d-flex align-items-center gap-3">
                                                <span class="dashboard-insight-icon">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M12 2.5 3.5 6.8 12 11l8.5-4.2L12 2.5Zm0 10.7L5.2 9.85 3.5 10.7 12 15l8.5-4.3-1.7-.85L12 13.2Zm0 4L5.2 13.85l-1.7.85L12 19l8.5-4.3-1.7-.85L12 17.2Z"/>
                                                    </svg>
                                                </span>
                                                <span>Berbasis Data dan Wilayah</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="nav nav-pills gap-2 mb-3 dashboard-tabs" role="tablist" aria-label="Pilihan grafik dashboard">
                            <button type="button" class="btn dashboard-tab active" data-chart-mode="kinerja">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/></svg>
                                <span>Kinerja</span>
                            </button>

                            <button type="button" class="btn dashboard-tab" data-chart-mode="wilayah">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/></svg>
                                <span>Wilayah</span>
                            </button>

                            <button type="button" class="btn dashboard-tab" data-chart-mode="legalitas">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2.75h9.25L20 7.5v13.75H6A2 2 0 0 1 4 19.25V4.75a2 2 0 0 1 2-2Zm8 1.75v4h4l-4-4ZM8 12h8v1.75H8V12Zm0 4h8v1.75H8V16Z"/></svg>
                                <span>Legalitas</span>
                            </button>
                        </div>

                        <div class="card border-0 chart-card">
                            <div class="card-body p-3 p-lg-4">
                                <div class="row align-items-start align-items-lg-center g-3 mb-3 chart-head">
                                    <div class="col-12 col-lg">
                                        <strong id="mainChartTitle">Tren Perkembangan UMKM</strong>
                                        <span id="mainChartSubtitle">Ringkasan data dalam periode pemantauan</span>
                                    </div>

                                    <div class="col-12 col-lg-auto">
                                        <div class="d-flex flex-wrap justify-content-start justify-content-lg-end gap-2 chart-head-badges">
                                            <span class="badge rounded-pill landing-preview-badge">Preview publik</span>
                                            <span class="badge rounded-pill chart-region-badge" data-public-chart-region>Kota Lubuklinggau</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="chart-canvas-wrap">
                                    <canvas id="landingMainChart"></canvas>
                                </div>

                                <div class="row g-3 mt-3 chart-summary">
                                    <div class="col-12 col-md-4">
                                        <div class="card h-100 border-0 chart-summary-card">
                                            <div class="card-body">
                                                <span>Filter</span>
                                                <strong id="chartSummaryOne">Wilayah, bidang usaha, periode</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <div class="card h-100 border-0 chart-summary-card">
                                            <div class="card-body">
                                                <span>Tampilan</span>
                                                <strong id="chartSummaryTwo">Grafik, indikator, dan ringkasan</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <div class="card h-100 border-0 chart-summary-card">
                                            <div class="card-body">
                                                <span>Fokus</span>
                                                <strong id="chartSummaryThree">Perkembangan UMKM</strong>
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

        <section id="ringkasan" class="summary-section py-5">
            <div class="container">
                <div class="card border-0 summary-shell reveal">
                    <div class="card-body p-4 p-xl-5">
                        <div class="row align-items-xl-center g-4 g-xl-5">
                            <div class="col-12 col-lg-10 col-xl-5 mx-lg-auto mx-xl-0">
                                <div class="summary-copy">
                                    <x-umkm.section-pill>
                                        <x-slot:icon>
                                            <svg viewBox="0 0 24 24"><path d="M5 4h14v2H5V4Zm0 5h14v2H5V9Zm0 5h10v2H5v-2Zm0 5h7v2H5v-2Z"/></svg>
                                        </x-slot:icon>
                                        Ringkasan sistem
                                    </x-umkm.section-pill>

                                    <h2 class="display-6 fw-bold mt-3 mb-3">
                                        Data tersusun, Visual Analitik Interaktif, Keputusan mudah terarah
                                    </h2>

                                    <p class="lead mb-0">
                                        Monitoring UMKM menyatukan pengelolaan data dan visualisasi agar informasi usaha
                                        dapat dipantau secara cepat tanpa kehilangan konteks penting.
                                    </p>
                                </div>
                            </div>

                            <div class="col-12 col-lg-10 col-xl-6 ms-xl-auto mx-lg-auto mx-xl-0">
                                <div class="row g-3 summary-list">
                                    <div class="col-12">
                                        <div class="card h-100 border-0 summary-item-card">
                                            <div class="card-body d-flex align-items-center gap-3">
                                                <span class="summary-item-icon">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm3 5h8V6H8v2Zm0 5h8v-2H8v2Zm0 5h5v-2H8v2Z"/></svg>
                                                </span>
                                                <span>Data usaha dikelola dalam struktur yang rapi.</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card h-100 border-0 summary-item-card">
                                            <div class="card-body d-flex align-items-center gap-3">
                                                <span class="summary-item-icon">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/></svg>
                                                </span>
                                                <span>Informasi ditampilkan dalam dashboard visual.</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card h-100 border-0 summary-item-card">
                                            <div class="card-body d-flex align-items-center gap-3">
                                                <span class="summary-item-icon">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 7v10l9 5 9-5V7l-9-5Zm0 2.3L17.8 7 12 9.7 6.2 7 12 4.3ZM5 8.6l6 2.8v7.9l-6-3.4V8.6Zm8 10.7v-7.9l6-2.8v7.3l-6 3.4Z"/></svg>
                                                </span>
                                                <span>Ringkasan membantu monitoring dan evaluasi.</span>
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

        <section id="cta" class="cta-section py-5">
            <div class="container">
                <div class="card border-0 cta-panel reveal">
                    <div class="card-body p-4 p-xl-5">
                        <div class="row align-items-center g-4">
                            <div class="col-12 col-lg-10 col-xl-8 mx-lg-auto mx-xl-0">
                                <x-umkm.section-pill>
                                    <x-slot:icon>
                                        <svg viewBox="0 0 24 24"><path d="M12 2 3 7v10l9 5 9-5V7l-9-5Zm0 2.3L17.8 7 12 9.7 6.2 7 12 4.3ZM5 8.6l6 2.8v7.9l-6-3.4V8.6Zm8 10.7v-7.9l6-2.8v7.3l-6 3.4Z"/></svg>
                                    </x-slot:icon>
                                    Monitoring UMKM
                                </x-umkm.section-pill>

                                <h2 class="display-6 fw-bold mt-3 mb-3">
                                    Kelola data UMKM dalam dashboard
                                </h2>

                                <p class="lead mb-0">
                                    Masuk ke sistem untuk mengakses dashboard, modul pengelolaan data,
                                    peta sebaran, dan ringkasan informasi pendukung keputusan.
                                </p>
                            </div>

                            <div class="col-12 col-lg-10 col-xl-auto ms-xl-auto mx-lg-auto mx-xl-0">
                                <span data-login-mount
                                      data-login-key="cta-login"
                                      data-login-label="Masuk ke Sistem"
                                      data-login-class="btn btn-light btn-lg cta-button w-100 w-xl-auto"></span>
                            </div>
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
