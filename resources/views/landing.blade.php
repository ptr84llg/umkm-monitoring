@extends('layouts.public')

@php($pageCss = ['landing.css'])

@section('title', 'Monitoring UMKM')

@section('content')
<div class="landing-shell">
    <nav class="navbar navbar-expand-lg landing-nav sticky-top">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center gap-3" href="{{ url('/') }}">
                <span class="brand-mark">MU</span>
                <span>
                    <span class="d-block fw-bold">Monitoring UMKM</span>
                    <span class="d-block small text-secondary">Dinas Koperasi & UMKM</span>
                </span>
            </a>
            <div class="d-flex gap-2 ms-auto">
                <a class="btn btn-outline-secondary" href="{{ route('login') }}">Masuk</a>
                <a class="btn btn-success" href="{{ route('dashboard.interactive') }}">Lihat Dashboard</a>
            </div>
        </div>
    </nav>

    <main>
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <span class="badge text-bg-light border mb-3">Platform monitoring data UMKM</span>
                        <h1 class="hero-title mb-4">Monitoring UMKM</h1>
                        <p class="hero-copy mb-4">Pantau data resmi, status legalitas, sebaran wilayah, kinerja periodik, usulan pembaruan, survei, dan validasi ahli dalam satu ruang kerja yang rapi.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <a class="btn btn-success btn-lg px-4" href="{{ route('login') }}">Masuk Sistem</a>
                            <a class="btn btn-outline-dark btn-lg px-4" href="{{ route('dashboard.interactive') }}">Buka Analitik</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="dashboard-preview">
                            <div class="preview-topbar d-flex align-items-center justify-content-between px-3">
                                <div class="d-flex align-items-center gap-2"><span class="badge rounded-pill text-bg-success">Live</span><span class="fw-semibold">Ringkasan Data Resmi</span></div>
                                <span class="small text-secondary">Asia/Jakarta</span>
                            </div>
                            <div class="p-3 p-md-4">
                                <div class="row g-3 mb-3">
                                    <div class="col-6 col-md-4"><div class="stat-tile p-3"><div class="small text-secondary">UMKM Resmi</div><div class="stat-value">1.248</div></div></div>
                                    <div class="col-6 col-md-4"><div class="stat-tile p-3"><div class="small text-secondary">Legalitas</div><div class="stat-value">842</div></div></div>
                                    <div class="col-12 col-md-4"><div class="stat-tile p-3"><div class="small text-secondary">Perlu Review</div><div class="stat-value">36</div></div></div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-7"><div class="insight-panel p-3"><div class="d-flex justify-content-between mb-3"><strong>Tren Kinerja</strong><span class="small text-secondary">12 bulan</span></div><div class="chart-bars d-flex align-items-end gap-2" style="height: 152px;"><span style="height: 42%"></span><span style="height: 58%"></span><span style="height: 48%"></span><span style="height: 68%"></span><span style="height: 76%"></span><span style="height: 61%"></span><span style="height: 86%"></span></div></div></div>
                                    <div class="col-md-5"><div class="map-panel p-3 position-relative" style="height: 190px; background: #f3f7f5;"><strong>Sebaran Wilayah</strong><span class="map-dot" style="left: 24%; top: 58%"></span><span class="map-dot" style="left: 52%; top: 38%"></span><span class="map-dot" style="left: 68%; top: 66%"></span><span class="map-dot" style="left: 38%; top: 76%"></span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="feature-row">
            <div class="container">
                <div class="row g-3">
                    <div class="col-md-4"><div class="feature-item p-4"><h2 class="h5">Data Resmi & Usulan</h2><p class="text-secondary mb-0">Pisahkan data UMKM resmi dari usulan pembaruan agar validasi tetap terkendali.</p></div></div>
                    <div class="col-md-4"><div class="feature-item p-4"><h2 class="h5">Audit & Keamanan</h2><p class="text-secondary mb-0">Role, permission, log audit, guard internal API, dan watermark ekspor tersedia sebagai fondasi.</p></div></div>
                    <div class="col-md-4"><div class="feature-item p-4"><h2 class="h5">Analitik Operasional</h2><p class="text-secondary mb-0">Ringkasan legalitas, KBLI, wilayah, performa, survei, dan validasi ahli siap dikembangkan.</p></div></div>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
