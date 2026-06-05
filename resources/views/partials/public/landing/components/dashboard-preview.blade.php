@php
    $publicLandingAnalytics = $publicLandingAnalytics ?? \App\Support\PublicLanding\PublicLandingData::analytics();
@endphp

<div class="container-fluid px-3 px-lg-4">
    <div class="analytics-section-head reveal mb-4">
        <div class="row align-items-end g-3">
            <div class="col-12 col-xl-7">
                <span class="landing-eyebrow">Pusat data publik</span>
                <h2 class="display-6 fw-bold mt-2 mb-2">Pusat Analitik & Wawasan Interaktif</h2>
                <p class="lead mb-0">Jelajahi data dan temukan insight untuk mendukung pembinaan, promosi, dan monitoring UMKM.</p>
            </div>
        </div>
    </div>

    <div class="card border-0 analytics-filter-card analytics-filter-sync-card reveal mb-4">
        <div class="card-body p-3 p-xl-4">
            <div class="filter-sync-head d-flex align-items-start gap-3 mb-3">
                <span class="filter-sync-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/></svg>
                </span>
                <div class="filter-sync-title">
                    <strong>Kontrol Data Publik</strong>
                    <p class="mb-0">Filter otomatis mengikuti konteks wilayah preview yang diterapkan pada Peta Sebaran.</p>
                    <small class="filter-sync-context">Konteks aktif: <b data-public-active-context-label>Kota Lubuklinggau</b></small>
                </div>
            </div>

            <form class="row g-3 align-items-end" autocomplete="off">
                <div class="col-12 col-lg-6 col-xl-3">
                    <label class="form-label" for="public-search">Cari Data</label>
                    <div class="input-group filter-input-group">
                        <span class="input-group-text"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 19.6-5.2-5.2a7.2 7.2 0 1 0-1.4 1.4l5.2 5.2L21 19.6ZM4.8 10.2a5.4 5.4 0 1 1 10.8 0 5.4 5.4 0 0 1-10.8 0Z"/></svg></span>
                        <input id="public-search" type="search" class="form-control" placeholder="Cari usaha, kategori, atau lokasi...">
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl">
                    <label class="form-label" for="filter-kecamatan">Kecamatan</label>
                    <div class="filter-select-wrap">
                        <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Z"/></svg></span>
                        <select id="filter-kecamatan" class="form-select" data-public-filter-district><option value="">Semua</option></select>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl">
                    <label class="form-label" for="filter-kelurahan">Kelurahan</label>
                    <div class="filter-select-wrap">
                        <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 4-8 4-8-4 8-4Zm-6.5 7.2L12 13.5l6.5-3.3L20 11l-8 4-8-4 1.5-.8Zm0 4L12 17.5l6.5-3.3L20 15l-8 4-8-4 1.5-.8Z"/></svg></span>
                        <select id="filter-kelurahan" class="form-select" data-public-filter-village><option value="">Semua</option></select>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl">
                    <label class="form-label" for="filter-kategori">Kategori Usaha</label>
                    <div class="filter-select-wrap">
                        <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 5.4 5h13.2L20 10.5V12a3 3 0 0 1-5 2.24A3 3 0 0 1 12 15a3 3 0 0 1-3-0.76A3 3 0 0 1 4 12v-1.5ZM6 16h12v5H6v-5Z"/></svg></span>
                        <select id="filter-kategori" class="form-select"><option>Semua</option></select>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-lg-3 col-xl">
                    <label class="form-label" for="filter-skala">Skala Usaha</label>
                    <div class="filter-select-wrap">
                        <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/></svg></span>
                        <select id="filter-skala" class="form-select"><option>Semua</option></select>
                    </div>
                </div>

                <div class="col-12 col-md-4 col-lg-3 col-xl-auto">
                    <button type="button" class="btn btn-primary w-100 public-filter-btn">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7L4 5Z"/></svg>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 g-xl-4 analytics-card-grid">
        <div class="col-12 col-lg-6 col-xxl-3">
            <article class="card h-100 border-0 analytics-card reveal">
                <div class="card-body">
                    <div class="analytics-card-head">
                        <h3>Komposisi Kategori Usaha</h3>
                        <span class="badge rounded-pill text-bg-light">Agregat</span>
                    </div>
                    <div class="donut-visual mx-auto my-3" aria-hidden="true">
                        <span>{{ $publicLandingAnalytics['scale']['total'] ?? '—' }}<small>Total UMKM</small></span>
                    </div>
                    <div class="analytics-legend">
                        @foreach (($publicLandingAnalytics['scale']['items'] ?? []) as $item)
                            <span><i class="{{ $item['class'] ?? '' }}"></i>{{ $item['label'] ?? 'Kategori' }} <strong>{{ $item['percent'] ?? '—' }}</strong></span>
                        @endforeach
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-lg-6 col-xxl-3">
            <article class="card h-100 border-0 analytics-card reveal reveal-delay-1">
                <div class="card-body">
                    <div class="analytics-card-head">
                        <h3>Status Keterpetaan Data</h3>
                        <span class="badge rounded-pill text-bg-light">Public-safe</span>
                    </div>
                    <div class="mini-line-chart" aria-hidden="true">
                        @foreach (($publicLandingAnalytics['trend_points'] ?? []) as $point)
                            <span class="mini-point {{ $point['class'] ?? '' }}">{{ $point['value'] ?? '—' }}</span>
                        @endforeach
                    </div>
                    <small class="text-muted">Data hingga {{ $publicLandingAnalytics['updated_at_label'] ?? 'periode terakhir' }}</small>
                </div>
            </article>
        </div>

        <div class="col-12 col-lg-6 col-xxl-3">
            <article class="card h-100 border-0 analytics-card reveal reveal-delay-2">
                <div class="card-body">
                    <div class="analytics-card-head">
                        <h3>Distribusi per Kecamatan</h3>
                        <span class="badge rounded-pill text-bg-light">Top wilayah</span>
                    </div>

                    <div class="bar-list">
                        @foreach (($publicLandingAnalytics['districts'] ?? []) as $district)
                            <div class="bar-item">
                                <span>{{ $district['label'] ?? 'Wilayah' }}</span>
                                <strong>{{ $district['value'] ?? '—' }}</strong>
                                <i class="bar-fill {{ $district['bar_class'] ?? 'bar-w-56' }}" aria-hidden="true"></i>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-lg-6 col-xxl-3">
            <article class="card h-100 border-0 analytics-card reveal reveal-delay-3">
                <div class="card-body">
                    <div class="analytics-card-head">
                        <h3>Peta Kepadatan UMKM</h3>
                        <span class="badge rounded-pill text-bg-light">Heatmap</span>
                    </div>
                    <div class="heatmap-card" aria-hidden="true">
                        <span class="heat h1"></span>
                        <span class="heat h2"></span>
                        <span class="heat h3"></span>
                        <span class="heat h4"></span>
                        <span class="heat h5"></span>
                    </div>
                    <small class="text-muted">Semakin merah, semakin padat.</small>
                </div>
            </article>
        </div>
    </div>
</div>

