@extends('layouts.public')

@php
    $assetProfile = 'landing';
    $vendorCss = [
        'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
    ];
    $vendorJs = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js',
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
                                    Pantau perkembangan UMKM melalui data usaha yang tersusun, ringkasan indikator,
                                    visualisasi wilayah, dan dashboard interaktif yang membantu proses monitoring
                                    serta pengambilan keputusan.
                                </p>

                                <div class="hero-actions">
                                    <a class="btn btn-success btn-lg landing-main-btn" href="{{ route('login') }}">
                                        Masuk ke Sistem
                                    </a>
                                    <a class="btn btn-outline-dark btn-lg landing-outline-btn" href="#dashboard">
                                        Lihat Dashboard
                                    </a>
                                </div>

                                <div class="hero-inline">
                                    <span>Profil UMKM</span>
                                    <span>Visual Analitik</span>
                                    <span>Peta Sebaran</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="hero-board reveal reveal-delay-1" data-tilt-card>
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
                                        <canvas id="heroMiniChart" height="170"></canvas>
                                    </div>

                                    <div class="board-bottom">
                                        <div>
                                            <span class="status-dot"></span>
                                            <strong>Data usaha</strong>
                                            <small>terkelola</small>
                                        </div>
                                        <div>
                                            <span class="status-dot gold"></span>
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
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-5">
                            <span class="landing-pill">Dashboard interaktif</span>
                            <h2>Informasi UMKM tampil dalam visual yang lebih hidup</h2>
                            <p>
                                Dashboard membantu membaca kondisi UMKM berdasarkan indikator utama, bidang usaha,
                                perkembangan data, dan sebaran wilayah dalam tampilan yang lebih ringkas.
                            </p>

                            <div class="dashboard-tabs" role="tablist" aria-label="Pilihan grafik dashboard">
                                <button type="button" class="dashboard-tab active" data-chart-mode="kinerja">Kinerja</button>
                                <button type="button" class="dashboard-tab" data-chart-mode="wilayah">Wilayah</button>
                                <button type="button" class="dashboard-tab" data-chart-mode="legalitas">Legalitas</button>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="chart-card">
                                <div class="chart-head">
                                    <div>
                                        <strong id="mainChartTitle">Tren Perkembangan UMKM</strong>
                                        <span id="mainChartSubtitle">Ringkasan data dalam periode pemantauan</span>
                                    </div>
                                    <span class="chart-badge">Chart.js</span>
                                </div>

                                <div class="chart-canvas-wrap">
                                    <canvas id="landingMainChart" height="250"></canvas>
                                    <div class="chart-fallback" id="chartFallback" hidden>
                                        <span></span><span></span><span></span><span></span><span></span><span></span>
                                    </div>
                                </div>

                                <div class="chart-summary">
                                    <div>
                                        <span>Filter</span>
                                        <strong>Wilayah, bidang usaha, periode</strong>
                                    </div>
                                    <div>
                                        <span>Tampilan</span>
                                        <strong>Grafik, indikator, dan ringkasan</strong>
                                    </div>
                                </div>
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
                        <span>01</span>
                        <h3>Data UMKM</h3>
                        <p>Profil usaha, pemilik, legalitas, produk, bidang usaha, lokasi, dan status data.</p>
                    </article>

                    <article class="module-card reveal reveal-delay-1">
                        <span>02</span>
                        <h3>Dashboard</h3>
                        <p>Indikator, grafik, komposisi data, dan ringkasan yang mudah dibaca.</p>
                    </article>

                    <article class="module-card reveal reveal-delay-2">
                        <span>03</span>
                        <h3>Peta Sebaran</h3>
                        <p>Visualisasi lokasi untuk membaca persebaran dan konsentrasi UMKM.</p>
                    </article>

                    <article class="module-card reveal reveal-delay-3">
                        <span>04</span>
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
