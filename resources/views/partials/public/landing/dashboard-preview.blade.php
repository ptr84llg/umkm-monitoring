<div class="container-fluid px-3 px-lg-4">
    <div class="analytics-section-head reveal mb-4">
        <div class="row align-items-end g-3">
            <div class="col-12 col-xl-7">
                <span class="landing-eyebrow">Pusat data publik</span>
                <h2 class="display-6 fw-bold mt-2 mb-2">Pusat Analitik & Wawasan Interaktif</h2>
                <p class="lead mb-0">Jelajahi data dan temukan insight untuk mendukung pembinaan, promosi, dan monitoring UMKM.</p>
            </div>
            <div class="col-12 col-xl-auto ms-xl-auto">
                <button type="button" class="btn btn-outline-dark public-report-btn">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 3h2v10.2l3.6-3.6L18 11l-6 6-6-6 1.4-1.4 3.6 3.6V3Zm-6 16h14v2H5v-2Z"/></svg>
                    <span>Unduh Laporan</span>
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 analytics-filter-card reveal mb-4">
        <div class="card-body p-3 p-xl-4">
            <form class="row g-3 align-items-end" autocomplete="off">
                <div class="col-12 col-xl-3">
                    <label class="form-label" for="public-search">Cari Data</label>
                    <div class="input-group">
                        <span class="input-group-text"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 19.6-5.2-5.2a7.2 7.2 0 1 0-1.4 1.4l5.2 5.2L21 19.6ZM4.8 10.2a5.4 5.4 0 1 1 10.8 0 5.4 5.4 0 0 1-10.8 0Z"/></svg></span>
                        <input id="public-search" type="search" class="form-control" placeholder="Cari usaha, kategori, atau lokasi...">
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filter-kecamatan">Kecamatan</label>
                    <select id="filter-kecamatan" class="form-select"><option>Semua</option></select>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filter-kelurahan">Kelurahan</label>
                    <select id="filter-kelurahan" class="form-select"><option>Semua</option></select>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filter-kategori">Kategori Usaha</label>
                    <select id="filter-kategori" class="form-select"><option>Semua</option></select>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label class="form-label" for="filter-skala">Skala Usaha</label>
                    <select id="filter-skala" class="form-select"><option>Semua</option></select>
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
                        <span style="--x: 8%; --y: 72%;">4.120</span>
                        <span style="--x: 28%; --y: 60%;">4.650</span>
                        <span style="--x: 48%; --y: 48%;">5.210</span>
                        <span style="--x: 68%; --y: 34%;">5.980</span>
                        <span style="--x: 88%; --y: 16%;">6.879</span>
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
                        @foreach ([
                            ['n' => 'Lubuklinggau Timur I', 'v' => '1.254', 'w' => 92],
                            ['n' => 'Lubuklinggau Barat I', 'v' => '1.102', 'w' => 80],
                            ['n' => 'Lubuklinggau Selatan I', 'v' => '1.021', 'w' => 72],
                            ['n' => 'Lubuklinggau Utara II', 'v' => '889', 'w' => 64],
                            ['n' => 'Lubuklinggau Timur II', 'v' => '792', 'w' => 56],
                        ] as $row)
                            <div class="bar-item">
                                <span>{{ $row['n'] }}</span>
                                <strong>{{ $row['v'] }}</strong>
                                <i style="--w: {{ $row['w'] }}%;"></i>
                            </div>
                        @endforeach
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