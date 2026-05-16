@extends('layouts.public')

@php
    $assetProfile = 'landing';
    $vendorJs = [
        asset('assets/vendor/chartjs/chart.umd.min.js'),
    ];
    $pageCss = ['landing.css'];
    $pageJs = ['landing.js'];
@endphp

@section('title', 'UMKM Monitoring | Sistem Informasi UMKM')

@section('content')
<div class="umkm-landing">
    <div class="landing-gradient gradient-a" data-parallax="0.08"></div>
    <div class="landing-gradient gradient-b" data-parallax="0.12"></div>

    <header class="landing-header" data-landing-header>
        <div class="container">
            <nav class="landing-nav" aria-label="Navigasi utama">
                <a class="landing-brand" href="{{ url('/') }}" aria-label="UMKM Monitoring">
                    <span class="landing-brand-mark">MU</span>
                    <span class="landing-brand-text">
                        <strong>UMKM Monitoring</strong>
                        <small>Sistem Informasi UMKM</small>
                    </span>
                </a>

                <div class="landing-menu">
                    <a href="#dashboard">Dashboard</a>
                    <a href="#modul">Modul</a>
                    <a href="#ringkasan">Ringkasan</a>
                </div>

                <div class="landing-nav-actions">
                    <a class="btn btn-light landing-login-btn" href="{{ route('login') }}">Masuk</a>
                    <a class="btn btn-success landing-main-btn" href="#dashboard">Jelajahi</a>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="container">
                <div class="hero-shell">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-6">
                            <div class="hero-copy reveal">
                                <span class="landing-pill">Sistem informasi untuk ekosistem UMKM</span>
                                <h1>UMKM Monitoring berbasis Data</h1>
                                <p>
                                    Temukan ringkasan usaha, persebaran wilayah, status legalitas, dan perkembangan
                                    aktivitas UMKM dalam tampilan dashboard yang mudah dibaca untuk membantu pemantauan
                                    program dan pengambilan keputusan.
                                </p>

                                <div class="hero-actions">
                                    <a class="btn btn-success btn-lg landing-main-btn" href="{{ route('login') }}">
                                        Masuk ke Sistem
                                    </a>
                                    <a class="btn btn-outline-dark btn-lg landing-outline-btn" href="#dashboard">
                                        Lihat Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="hero-board reveal reveal-delay-1" data-tilt-card>
                                <div class="board-source">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 2.75c-3.45 0-6.25 2.8-6.25 6.25 0 4.5 6.25 12.25 6.25 12.25S18.25 13.5 18.25 9c0-3.45-2.8-6.25-6.25-6.25Zm0 8.6a2.35 2.35 0 1 1 0-4.7 2.35 2.35 0 0 1 0 4.7Z"/>
                                    </svg>
                                    <span>Sumber data: Kota Lubuklinggau</span>
                                </div>

                                <div class="board-window">
                                    <div class="board-top">
                                        <div class="board-dots">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                        <strong>UMKM Dashboard</strong>
                                        <small>preview</small>
                                    </div>

                                    <div class="board-metrics">
                                        <div>
                                            <span>UMKM Terdata</span>
                                            <strong class="count-up" data-count="1248">0</strong>
                                        </div>
                                        <div>
                                            <span>Legalitas</span>
                                            <strong class="count-up" data-count="842">0</strong>
                                        </div>
                                        <div>
                                            <span>Pembaruan</span>
                                            <strong class="count-up" data-count="36">0</strong>
                                        </div>
                                    </div>

                                    <div class="board-chart-wrap">
                                        <canvas id="heroMiniChart"></canvas>
                                        <div class="hero-chart-fallback" id="heroChartFallback" hidden>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>

                                    <div class="board-bottom">
                                        <div>
                                            <span class="status-icon">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v13a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 18.5v-13Zm4 2.25a.75.75 0 0 0 0 1.5h8a.75.75 0 0 0 0-1.5H8Zm0 4a.75.75 0 0 0 0 1.5h8a.75.75 0 0 0 0-1.5H8Zm0 4a.75.75 0 0 0 0 1.5h5a.75.75 0 0 0 0-1.5H8Z"/>
                                                </svg>
                                            </span>
                                            <strong>Data usaha</strong>
                                            <small>terkelola</small>
                                        </div>
                                        <div>
                                            <span class="status-icon gold">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M12 2.5 3.5 6.25v11.5L12 21.5l8.5-3.75V6.25L12 2.5Zm0 2.2 5.5 2.43L12 9.55 6.5 7.13 12 4.7Zm-6.5 4.1 5.5 2.43v7.36l-5.5-2.43V8.8Zm7.5 9.79v-7.36l5.5-2.43v7.36L13 18.59Z"/>
                                                </svg>
                                            </span>
                                            <strong>Wilayah</strong>
                                            <small>terpantau</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hero-marquee" aria-label="Ruang lingkup sistem">
                        <div class="marquee-track">
                            <span>Data UMKM</span>
                            <span>Dashboard</span>
                            <span>Peta Sebaran</span>
                            <span>Indikator Kinerja</span>
                            <span>Laporan Ringkas</span>
                            <span>Umpan Balik</span>
                            <span>Data UMKM</span>
                            <span>Dashboard</span>
                            <span>Peta Sebaran</span>
                            <span>Indikator Kinerja</span>
                            <span>Laporan Ringkas</span>
                            <span>Umpan Balik</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="dashboard" class="dashboard-section">
            <div class="container">
                <div class="dashboard-panel reveal">
                    <div class="dashboard-panel-head">
                        <div>
                            <span class="landing-pill">Dashboard interaktif</span>
                            <h2>Informasi UMKM tampil dalam visual yang lebih hidup</h2>
                            <p>
                                Dashboard membantu membaca kondisi UMKM berdasarkan indikator utama, bidang usaha,
                                perkembangan data, dan sebaran wilayah dalam tampilan yang lebih ringkas.
                            </p>
                        </div>

                        <div class="dashboard-insight">
                            <div>
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/>
                                </svg>
                                <span>Visual dinamis</span>
                            </div>
                            <div>
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/>
                                </svg>
                                <span>Berbasis wilayah</span>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-tabs" role="tablist" aria-label="Pilihan grafik dashboard">
                        <button type="button" class="dashboard-tab active" data-chart-mode="kinerja">Kinerja</button>
                        <button type="button" class="dashboard-tab" data-chart-mode="wilayah">Wilayah</button>
                        <button type="button" class="dashboard-tab" data-chart-mode="legalitas">Legalitas</button>
                    </div>

                    <div class="chart-card">
                        <div class="chart-head">
                            <div>
                                <strong id="mainChartTitle">Tren Perkembangan UMKM</strong>
                                <span id="mainChartSubtitle">Ringkasan data dalam periode pemantauan</span>
                            </div>
                            <span class="chart-badge">Chart.js</span>
                        </div>

                        <div class="chart-canvas-wrap">
                            <canvas id="landingMainChart"></canvas>
                            <div class="chart-fallback" id="chartFallback" hidden>
                                <span></span><span></span><span></span><span></span><span></span><span></span>
                            </div>
                        </div>

                        <div class="chart-summary">
                            <div>
                                <span>Filter</span>
                                <strong id="chartSummaryOne">Wilayah, bidang usaha, periode</strong>
                            </div>
                            <div>
                                <span>Tampilan</span>
                                <strong id="chartSummaryTwo">Grafik, indikator, dan ringkasan</strong>
                            </div>
                            <div>
                                <span>Fokus</span>
                                <strong id="chartSummaryThree">Perkembangan UMKM</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="modul" class="module-section">
            <div class="container">
                <div class="section-heading reveal">
                    <span class="landing-pill">Modul sistem</span>
                    <h2>Ruang kerja untuk mengelola dan memantau data UMKM</h2>
                    <p>
                        Modul disusun agar pengelolaan data, pembacaan indikator, dan penyajian informasi
                        dapat dilakukan dalam alur yang lebih tertata.
                    </p>
                </div>

                <div class="module-grid">
                    <article class="module-card reveal">
                        <span class="module-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm3 5h8V6H8v2Zm0 5h8v-2H8v2Zm0 5h5v-2H8v2Z"/>
                            </svg>
                        </span>
                        <h3>Data UMKM</h3>
                        <p>Profil usaha, pemilik, legalitas, produk, bidang usaha, lokasi, dan status data.</p>
                    </article>

                    <article class="module-card reveal reveal-delay-1">
                        <span class="module-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/>
                            </svg>
                        </span>
                        <h3>Dashboard</h3>
                        <p>Indikator, grafik, komposisi data, dan ringkasan yang mudah dibaca.</p>
                    </article>

                    <article class="module-card reveal reveal-delay-2">
                        <span class="module-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/>
                            </svg>
                        </span>
                        <h3>Peta Sebaran</h3>
                        <p>Visualisasi lokasi untuk membaca persebaran dan konsentrasi UMKM.</p>
                    </article>

                    <article class="module-card reveal reveal-delay-3">
                        <span class="module-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M6 2.75h9.25L20 7.5v13.75H6A2 2 0 0 1 4 19.25V4.75a2 2 0 0 1 2-2Zm8 1.75v4h4l-4-4ZM8 12h8v1.75H8V12Zm0 4h8v1.75H8V16Z"/>
                            </svg>
                        </span>
                        <h3>Laporan</h3>
                        <p>Ringkasan informasi untuk monitoring, evaluasi, dan tindak lanjut.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="ringkasan" class="summary-section">
            <div class="container">
                <div class="summary-shell reveal">
                    <div class="summary-copy">
                        <span class="landing-pill">Ringkasan sistem</span>
                        <h2>Data tersusun, visual lebih jelas, keputusan lebih terarah</h2>
                        <p>
                            UMKM Monitoring menyatukan pengelolaan data dan visualisasi agar informasi usaha
                            dapat dipantau secara cepat tanpa kehilangan konteks penting.
                        </p>
                    </div>

                    <div class="summary-list">
                        <div>
                            <strong>01</strong>
                            <span>Data usaha dikelola dalam struktur yang rapi.</span>
                        </div>
                        <div>
                            <strong>02</strong>
                            <span>Informasi ditampilkan dalam dashboard visual.</span>
                        </div>
                        <div>
                            <strong>03</strong>
                            <span>Ringkasan membantu monitoring dan evaluasi.</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="cta" class="cta-section">
            <div class="container">
                <div class="cta-panel reveal">
                    <div>
                        <span class="landing-pill">UMKM Monitoring</span>
                        <h2>Kelola data UMKM dalam dashboard</h2>
                        <p>
                            Masuk ke sistem untuk mengakses dashboard, modul pengelolaan data,
                            peta sebaran, dan ringkasan informasi pendukung keputusan.
                        </p>
                    </div>

                    <a class="btn btn-light btn-lg cta-button" href="{{ route('login') }}">
                        Masuk ke Sistem
                    </a>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
