@extends('layouts.public')

@php
    $assetProfile = 'landing';
    $pageCss = ['landing.css'];
@endphp

@section('title', 'UMKM Monitoring | Visual Analitik Interaktif')

@section('content')
<div class="umkm-landing">
    <header class="landing-header">
        <div class="container">
            <div class="landing-nav">
                <a class="landing-brand" href="{{ url('/') }}" aria-label="UMKM Monitoring">
                    <span class="landing-brand-mark">MU</span>
                    <span class="landing-brand-text">
                        <strong>UMKM Monitoring</strong>
                        <small>Visual Analitik Interaktif</small>
                    </span>
                </a>

                <div class="landing-nav-actions">
                    <a class="btn btn-light landing-login-btn" href="{{ route('login') }}">Masuk</a>
                    <a class="btn btn-success landing-main-btn" href="{{ route('login') }}">Buka Dashboard</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="landing-hero">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="hero-copy-block">
                            <span class="hero-eyebrow">Sistem informasi berbasis data</span>
                            <h1 class="hero-title">
                                Monitoring Kinerja UMKM dengan Visual Analitik Interaktif
                            </h1>
                            <p class="hero-description">
                                Sistem ini dirancang untuk membantu pengelolaan data UMKM, pemantauan kinerja,
                                pembacaan sebaran wilayah, evaluasi legalitas, survei pengguna, dan validasi ahli
                                dalam satu ruang kerja yang terukur.
                            </p>

                            <div class="hero-action-row">
                                <a class="btn btn-success btn-lg landing-main-btn" href="{{ route('login') }}">
                                    Masuk ke Sistem
                                </a>
                                <a class="btn btn-outline-dark btn-lg landing-outline-btn" href="#ruang-lingkup">
                                    Lihat Ruang Lingkup
                                </a>
                            </div>

                            <div class="hero-note">
                                <span class="note-dot"></span>
                                <span>Data sensitif dikelola melalui akses berbasis peran, audit, dan pembatasan API internal.</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="analytics-showcase" aria-label="Pratinjau visual analitik UMKM">
                            <div class="showcase-header">
                                <div>
                                    <span class="showcase-status">Analitik</span>
                                    <strong>Ringkasan Monitoring</strong>
                                </div>
                                <small>Basis data UMKM</small>
                            </div>

                            <div class="showcase-body">
                                <div class="metric-grid">
                                    <div class="metric-card">
                                        <span>UMKM Terdata</span>
                                        <strong>1.248</strong>
                                        <small>contoh ringkasan</small>
                                    </div>
                                    <div class="metric-card">
                                        <span>Legalitas Tercatat</span>
                                        <strong>842</strong>
                                        <small>status terisi</small>
                                    </div>
                                    <div class="metric-card">
                                        <span>Perlu Validasi</span>
                                        <strong>36</strong>
                                        <small>usulan data</small>
                                    </div>
                                </div>

                                <div class="showcase-panel-grid">
                                    <div class="trend-panel">
                                        <div class="panel-title-row">
                                            <strong>Tren Kinerja</strong>
                                            <small>periode monitoring</small>
                                        </div>
                                        <div class="bar-visual">
                                            <span class="bar bar-1"></span>
                                            <span class="bar bar-2"></span>
                                            <span class="bar bar-3"></span>
                                            <span class="bar bar-4"></span>
                                            <span class="bar bar-5"></span>
                                            <span class="bar bar-6"></span>
                                            <span class="bar bar-7"></span>
                                        </div>
                                    </div>

                                    <div class="map-summary-panel">
                                        <div class="panel-title-row">
                                            <strong>Sebaran Wilayah</strong>
                                            <small>agregat</small>
                                        </div>
                                        <div class="map-visual">
                                            <span class="map-region region-1"></span>
                                            <span class="map-region region-2"></span>
                                            <span class="map-region region-3"></span>
                                            <span class="map-region region-4"></span>
                                            <span class="map-pin pin-1"></span>
                                            <span class="map-pin pin-2"></span>
                                            <span class="map-pin pin-3"></span>
                                            <span class="map-pin pin-4"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="showcase-footer">
                                    <span>Filter: wilayah, KBLI, legalitas, periode</span>
                                    <span>Privasi: agregasi & pembatasan akses</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="ruang-lingkup" class="scope-section">
            <div class="container">
                <div class="section-heading">
                    <span class="section-kicker">Ruang Lingkup Sistem</span>
                    <h2>Dirancang untuk monitoring, analisis, dan pengambilan keputusan UMKM</h2>
                    <p>
                        Fokus sistem bukan hanya menyimpan data, tetapi mengubah data UMKM menjadi informasi
                        yang dapat dibaca melalui indikator, grafik, peta, ringkasan, dan status validasi.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-xl-3">
                        <article class="scope-card">
                            <span class="scope-number">01</span>
                            <h3>Data UMKM</h3>
                            <p>Pengelolaan data usaha, pemilik, bidang usaha, legalitas, produk, lokasi, dan status pembaruan.</p>
                        </article>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <article class="scope-card">
                            <span class="scope-number">02</span>
                            <h3>Visual Analitik</h3>
                            <p>Dashboard indikator, tren kinerja, komposisi KBLI, status legalitas, tabel ringkasan, dan peta agregat.</p>
                        </article>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <article class="scope-card">
                            <span class="scope-number">03</span>
                            <h3>Survei Pengguna</h3>
                            <p>Instrumen survei mendukung pengumpulan umpan balik pengguna terhadap sistem dan kebutuhan informasi.</p>
                        </article>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <article class="scope-card">
                            <span class="scope-number">04</span>
                            <h3>Validasi Ahli</h3>
                            <p>Penilaian ahli sistem informasi, visualisasi data, dan keamanan data dikelola melalui instrumen terstruktur.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="workflow-section">
            <div class="container">
                <div class="workflow-card">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-4">
                            <span class="section-kicker">Alur Kerja Utama</span>
                            <h2>Dari data resmi menuju insight yang dapat ditindaklanjuti</h2>
                            <p>
                                Setiap informasi diproses melalui tahapan yang menjaga ketertelusuran data,
                                validasi, dan keamanan akses.
                            </p>
                        </div>
                        <div class="col-lg-8">
                            <div class="workflow-steps">
                                <div class="workflow-step">
                                    <span>1</span>
                                    <strong>Data Masuk</strong>
                                    <small>Data resmi, usulan pembaruan, survei, dan validasi ahli.</small>
                                </div>
                                <div class="workflow-step">
                                    <span>2</span>
                                    <strong>Validasi</strong>
                                    <small>Pemeriksaan status, kelengkapan, legalitas, lokasi, dan perubahan data.</small>
                                </div>
                                <div class="workflow-step">
                                    <span>3</span>
                                    <strong>Analitik</strong>
                                    <small>Data dibaca melalui indikator, grafik, peta, filter, dan ringkasan.</small>
                                </div>
                                <div class="workflow-step">
                                    <span>4</span>
                                    <strong>Keputusan</strong>
                                    <small>Informasi digunakan untuk monitoring, pelaporan, dan rekomendasi tindak lanjut.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="security-section">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="security-intro">
                            <span class="section-kicker">Keamanan & Tata Kelola</span>
                            <h2>Akses sistem dibatasi berdasarkan peran dan kebutuhan data</h2>
                            <p>
                                Sistem menempatkan keamanan sebagai fondasi, terutama untuk data UMKM,
                                koordinat lokasi, ekspor laporan, audit aktivitas, dan API internal.
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="security-grid">
                            <div class="security-item">
                                <strong>Role & Permission</strong>
                                <span>Akses disesuaikan dengan peran pengguna.</span>
                            </div>
                            <div class="security-item">
                                <strong>Audit Log</strong>
                                <span>Aktivitas penting dicatat untuk ketertelusuran.</span>
                            </div>
                            <div class="security-item">
                                <strong>API Internal</strong>
                                <span>Request dibatasi melalui origin, referer, fetch metadata, dan rate limit.</span>
                            </div>
                            <div class="security-item">
                                <strong>Ekspor Terkendali</strong>
                                <span>Laporan sensitif diarahkan melalui kontrol alasan, watermark, dan pembatasan akses.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-cta">
            <div class="container">
                <div class="cta-card">
                    <div>
                        <span class="section-kicker">Mulai Pemeriksaan Sistem</span>
                        <h2>Masuk untuk melanjutkan pengujian dashboard dan modul internal</h2>
                        <p>
                            Tahap ini masih berupa fondasi awal. Pengujian berikutnya dilakukan bertahap pada route,
                            layout dashboard, login, role, API internal, dan halaman modul.
                        </p>
                    </div>
                    <a class="btn btn-success btn-lg landing-main-btn" href="{{ route('login') }}">
                        Masuk ke Sistem
                    </a>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
