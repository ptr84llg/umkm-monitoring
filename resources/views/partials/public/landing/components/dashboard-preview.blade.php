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
            <div class="filter-sync-head d-flex flex-column flex-xl-row align-items-xl-center justify-content-xl-between gap-3 mb-3">
                <div class="filter-sync-title d-flex align-items-start gap-3">
                    <span class="filter-sync-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/></svg>
                    </span>
                    <div>
                        <strong>Kontrol Data Publik</strong>
                        <p class="mb-0">Filter mengikuti konteks wilayah preview dan menyajikan ringkasan agregat.</p>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm filter-region-sync-btn" data-region-open data-region-modal-open>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 6-6-2-6 2v15l6-2 6 2 6-2V4l-6 2Z"/></svg>
                    <span>Sinkron Wilayah</span>
                </button>
            </div>

            <form class="row g-3 align-items-end" autocomplete="off">
                <div class="col-12 col-xl-3">
                    <label class="form-label" for="public-search">Cari Data</label>
                    <div class="input-group filter-input-group">
                        <span class="input-group-text"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 19.6-5.2-5.2a7.2 7.2 0 1 0-1.4 1.4l5.2 5.2L21 19.6ZM4.8 10.2a5.4 5.4 0 1 1 10.8 0 5.4 5.4 0 0 1-10.8 0Z"/></svg></span>
                        <input id="public-search" type="search" class="form-control" placeholder="Cari usaha, kategori, atau lokasi...">
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filter-kecamatan">Kecamatan</label>
                    <div class="filter-select-wrap">
                        <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Z"/></svg></span>
                        <select id="filter-kecamatan" class="form-select"><option>Semua</option></select>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filter-kelurahan">Kelurahan</label>
                    <div class="filter-select-wrap">
                        <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 4-8 4-8-4 8-4Zm-6.5 7.2L12 13.5l6.5-3.3L20 11l-8 4-8-4 1.5-.8Zm0 4L12 17.5l6.5-3.3L20 15l-8 4-8-4 1.5-.8Z"/></svg></span>
                        <select id="filter-kelurahan" class="form-select"><option>Semua</option></select>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filter-kategori">Kategori Usaha</label>
                    <div class="filter-select-wrap">
                        <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 5.4 5h13.2L20 10.5V12a3 3 0 0 1-5 2.24A3 3 0 0 1 12 15a3 3 0 0 1-3-0.76A3 3 0 0 1 4 12v-1.5ZM6 16h12v5H6v-5Z"/></svg></span>
                        <select id="filter-kategori" class="form-select"><option>Semua</option></select>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filter-skala">Skala Usaha</label>
                    <div class="filter-select-wrap">
                        <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/></svg></span>
                        <select id="filter-skala" class="form-select"><option>Semua</option></select>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-xl-auto">
                    <button type="button" class="btn btn-primary w-100 public-filter-btn">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7L4 5Z"/></svg>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 g-xl-4 analytics-card-grid">
        <div class="col-12 col-xl-3">
            <article class="card h-100 border-0 analytics-card reveal">
                <div class="card-body">
                    <div class="analytics-card-head">
                        <h3>Komposisi Skala Usaha</h3>
                        <span class="badge rounded-pill text-bg-light">Agregat</span>
                    </div>
                    <div class="donut-visual mx-auto my-3" aria-hidden="true">
                        <span>6.879<small>Total UMKM</small></span>
                    </div>
                    <div class="analytics-legend">
                        <span><i class="is-mikro"></i>Mikro <strong>56,5%</strong></span>
                        <span><i class="is-kecil"></i>Kecil <strong>29,5%</strong></span>
                        <span><i class="is-menengah"></i>Menengah <strong>14,0%</strong></span>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-3">
            <article class="card h-100 border-0 analytics-card reveal reveal-delay-1">
                <div class="card-body">
                    <div class="analytics-card-head">
                        <h3>Tren Pertumbuhan UMKM</h3>
                        <span class="badge rounded-pill text-bg-light">2022–2026</span>
                    </div>
                    <div class="mini-line-chart" aria-hidden="true">
                        <span class="mini-point point-01">4.120</span>
                        <span class="mini-point point-02">4.650</span>
                        <span class="mini-point point-03">5.210</span>
                        <span class="mini-point point-04">5.980</span>
                        <span class="mini-point point-05">6.879</span>
                    </div>
                    <small class="text-muted">Data hingga Juni 2026</small>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-3">
            <article class="card h-100 border-0 analytics-card reveal reveal-delay-2">
                <div class="card-body">
                    <div class="analytics-card-head">
                        <h3>Distribusi per Kecamatan</h3>
                        <span class="badge rounded-pill text-bg-light">Top wilayah</span>
                    </div>
                    <div class="bar-list">
                        <div class="bar-item">
                            <span>Lubuklinggau Timur I</span>
                            <strong>1.254</strong>
                            <i class="bar-fill bar-w-92" aria-hidden="true"></i>
                        </div>
                        <div class="bar-item">
                            <span>Lubuklinggau Barat I</span>
                            <strong>1.102</strong>
                            <i class="bar-fill bar-w-80" aria-hidden="true"></i>
                        </div>
                        <div class="bar-item">
                            <span>Lubuklinggau Selatan I</span>
                            <strong>1.021</strong>
                            <i class="bar-fill bar-w-72" aria-hidden="true"></i>
                        </div>
                        <div class="bar-item">
                            <span>Lubuklinggau Utara II</span>
                            <strong>889</strong>
                            <i class="bar-fill bar-w-64" aria-hidden="true"></i>
                        </div>
                        <div class="bar-item">
                            <span>Lubuklinggau Timur II</span>
                            <strong>792</strong>
                            <i class="bar-fill bar-w-56" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-12 col-xl-3">
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