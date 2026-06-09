@php
    // DOM awal hanya memuat wadah visual. Data agregat analitik diisi dari response backend setelah wilayah aktif dimuat.
@endphp

<div class="container-fluid px-3 px-lg-4" data-public-analytics-insight-root>
    <div class="analytics-section-head reveal mb-4">
        <div class="row align-items-end g-3">
            <div class="col-12 col-xl-8">
                <span class="landing-eyebrow">Pusat data publik</span>
                <h2 class="display-6 fw-bold mt-2 mb-2">Pusat Analitik & Wawasan Interaktif</h2>
                <p class="lead mb-0">Baca struktur usaha, pemasaran, kesiapan data, dan area pembanding berdasarkan wilayah aktif.</p>
            </div>
            <div class="col-12 col-xl-4 text-xl-end">
                <span class="public-analytics-badge">Ringkasan agregat wilayah</span>
            </div>
        </div>
    </div>

    <div class="public-analytics-control-grid reveal mb-4">
        <article class="card border-0 analytics-filter-card public-analytics-context-card h-100">
            <div class="card-body p-3 p-xl-4">
                <div class="filter-sync-head d-flex align-items-start gap-3 mb-3">
                    <span class="filter-sync-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/></svg>
                    </span>
                    <div class="filter-sync-title">
                        <strong>Konteks Wilayah Aktif</strong>
                        <p class="mb-0">Wilayah utama mengikuti pilihan pada peta sebaran atau modal pilih wilayah.</p>
                        <small class="filter-sync-context">
                            Konteks aktif: <b data-public-active-context-label data-public-analytics-context-label>Menunggu data</b>

                        </small>
                    </div>
                </div>

                <div class="public-analytics-context-path" data-public-analytics-context-path>
                    Menunggu wilayah aktif.
                </div>
            </div>
        </article>

        <article class="card border-0 analytics-filter-card public-analytics-filter-card h-100">
            <div class="card-body p-3 p-xl-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <strong class="public-analytics-filter-title">Filter Tampilan Analitik</strong>
                        <p class="mb-0 text-muted small">Filter membatasi grafik pada kelompok agregat yang tersedia di wilayah aktif.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-public-analytics-reset>
                        Reset
                    </button>
                </div>

                <form class="row g-3" autocomplete="off" data-public-analytics-filter-form>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="analytics-filter-kategori">Kategori Usaha</label>
                        <div class="filter-select-wrap">
                            <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 5.4 5h13.2L20 10.5V12a3 3 0 0 1-5 2.24A3 3 0 0 1 12 15a3 3 0 0 1-3-0.76A3 3 0 0 1 4 12v-1.5ZM6 16h12v5H6v-5Z"/></svg></span>
                            <select id="analytics-filter-kategori" class="form-select" data-public-analytics-filter="category">
                                <option value="">Semua kategori</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="analytics-filter-jenis">Jenis Usaha</label>
                        <div class="filter-select-wrap">
                            <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v4H4V5Zm0 6h7v8H4v-8Zm9 0h7v8h-7v-8Z"/></svg></span>
                            <select id="analytics-filter-jenis" class="form-select" data-public-analytics-filter="type">
                                <option value="">Semua jenis usaha</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="analytics-filter-pemasaran">Metode Pemasaran</label>
                        <div class="filter-select-wrap">
                            <span class="filter-field-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h10v2H4V5Zm0 4h16v2H4V9Zm0 4h12v2H4v-2Zm0 4h7v2H4v-2Z"/></svg></span>
                            <select id="analytics-filter-pemasaran" class="form-select" data-public-analytics-filter="marketing">
                                <option value="">Semua metode</option>
                            </select>
                        </div>
                    </div>
                </form>

                <div class="public-active-filter-strip mt-3" data-public-analytics-filter-strip>
                    <span>Semua data agregat ditampilkan.</span>
                </div>
            </div>
        </article>
    </div>

    <div class="row g-3 g-xl-4 public-insight-card-grid mb-4">
        <div class="col-12 col-md-6 col-xxl">
            <article class="card h-100 border-0 public-insight-card reveal" data-public-insight-card="total_umkm">
                <div class="card-body">
                    <span class="public-insight-kicker">Total UMKM</span>
                    <strong data-public-insight-value>—</strong>
                    <p data-public-insight-context>Jumlah UMKM pada wilayah aktif.</p>
                </div>
            </article>
        </div>
        <div class="col-12 col-md-6 col-xxl">
            <article class="card h-100 border-0 public-insight-card reveal reveal-delay-1" data-public-insight-card="dominant_category">
                <div class="card-body">
                    <span class="public-insight-kicker">Kategori dominan</span>
                    <strong data-public-insight-value>—</strong>
                    <p data-public-insight-context>Kelompok usaha paling besar.</p>
                </div>
            </article>
        </div>
        <div class="col-12 col-md-6 col-xxl">
            <article class="card h-100 border-0 public-insight-card reveal reveal-delay-2" data-public-insight-card="dominant_type">
                <div class="card-body">
                    <span class="public-insight-kicker">Jenis usaha dominan</span>
                    <strong data-public-insight-value>—</strong>
                    <p data-public-insight-context>Jenis usaha paling banyak.</p>
                </div>
            </article>
        </div>
        <div class="col-12 col-md-6 col-xxl">
            <article class="card h-100 border-0 public-insight-card reveal reveal-delay-3" data-public-insight-card="dominant_marketing">
                <div class="card-body">
                    <span class="public-insight-kicker">Pemasaran dominan</span>
                    <strong data-public-insight-value>—</strong>
                    <p data-public-insight-context>Metode pemasaran terbanyak.</p>
                </div>
            </article>
        </div>
        <div class="col-12 col-md-6 col-xxl">
            <article class="card h-100 border-0 public-insight-card reveal reveal-delay-3" data-public-insight-card="mapped_readiness">
                <div class="card-body">
                    <span class="public-insight-kicker">Keterpetaan lokasi</span>
                    <strong data-public-insight-value>—</strong>
                    <p data-public-insight-context>Persentase UMKM yang sudah memiliki titik lokasi.</p>
                </div>
            </article>
        </div>
    </div>

    <div class="card border-0 public-analytics-workspace reveal mb-4">
        <div class="card-body p-3 p-xl-4">
            <div class="public-workspace-head mb-3">
                <div>
                    <span>Visual Analitik Wilayah Aktif</span>
                    <strong>Grafik dan tabel mengikuti wilayah serta filter aktif</strong>
                </div>
                <p>Gunakan tab untuk membaca struktur usaha, pemasaran, kesiapan data, dan area pembanding.</p>
            </div>

            <ul class="nav nav-pills public-analytics-tabs mb-4" id="publicAnalyticsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="analytics-business-tab" data-bs-toggle="pill" data-bs-target="#analytics-business-pane" type="button" role="tab" aria-controls="analytics-business-pane" aria-selected="true">Struktur Usaha</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="analytics-marketing-tab" data-bs-toggle="pill" data-bs-target="#analytics-marketing-pane" type="button" role="tab" aria-controls="analytics-marketing-pane" aria-selected="false">Pemasaran</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="analytics-readiness-tab" data-bs-toggle="pill" data-bs-target="#analytics-readiness-pane" type="button" role="tab" aria-controls="analytics-readiness-pane" aria-selected="false">Kesiapan Data</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="analytics-area-tab" data-bs-toggle="pill" data-bs-target="#analytics-area-pane" type="button" role="tab" aria-controls="analytics-area-pane" aria-selected="false">Area Pembanding</button>
                </li>
            </ul>

            <div class="tab-content public-analytics-tab-content">
                <div class="tab-pane fade show active" id="analytics-business-pane" role="tabpanel" aria-labelledby="analytics-business-tab" tabindex="0">
                    <div class="row g-3 g-xl-4">
                        <div class="col-12 col-xl-5">
                            <div class="public-chart-panel h-100">
                                <div class="public-chart-head">
                                    <strong>Komposisi Kategori Usaha</strong>
                                    <small>Proporsi kategori pada wilayah aktif.</small>
                                </div>
                                <div class="public-chart-canvas" data-public-analytics-chart="category"></div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-7">
                            <div class="public-chart-panel h-100">
                                <div class="public-chart-head">
                                    <strong>Jenis Usaha Dominan</strong>
                                    <small>Jenis usaha terbesar sesuai filter kategori.</small>
                                </div>
                                <div class="public-chart-canvas" data-public-analytics-chart="types"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="analytics-marketing-pane" role="tabpanel" aria-labelledby="analytics-marketing-tab" tabindex="0">
                    <div class="row g-3 g-xl-4">
                        <div class="col-12 col-xl-5">
                            <div class="public-chart-panel h-100">
                                <div class="public-chart-head">
                                    <strong>Komposisi Metode Pemasaran</strong>
                                    <small>Distribusi metode pemasaran pada wilayah aktif.</small>
                                </div>
                                <div class="public-chart-canvas" data-public-analytics-chart="marketing"></div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-7">
                            <div class="public-insight-panel h-100" data-public-analytics-marketing-note>
                                <span>Ringkasan Pemasaran</span>
                                <p>Menunggu data pemasaran pada wilayah aktif.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="analytics-readiness-pane" role="tabpanel" aria-labelledby="analytics-readiness-tab" tabindex="0">
                    <div class="row g-3 g-xl-4">
                        <div class="col-12 col-xl-7">
                            <div class="public-chart-panel h-100">
                                <div class="public-chart-head">
                                    <strong>Kesiapan Data Lokasi</strong>
                                    <small>Perbandingan data terpetakan dan belum terpetakan.</small>
                                </div>
                                <div class="public-chart-canvas" data-public-analytics-chart="readiness"></div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-5">
                            <div class="public-readiness-stack" data-public-readiness-stack>
                                <div>
                                    <span>UMKM terpetakan</span>
                                    <strong data-public-readiness-mapped>—</strong>
                                </div>
                                <div>
                                    <span>Belum terpetakan</span>
                                    <strong data-public-readiness-unmapped>—</strong>
                                </div>
                                <div>
                                    <span>Catatan kualitas data</span>
                                    <strong data-public-readiness-note>—</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="analytics-area-pane" role="tabpanel" aria-labelledby="analytics-area-tab" tabindex="0">
                    <div class="row g-3 g-xl-4">
                        <div class="col-12 col-xl-6">
                            <div class="public-chart-panel h-100">
                                <div class="public-chart-head">
                                    <strong>Area Pembanding</strong>
                                    <small>Agregat pembanding sesuai level wilayah aktif.</small>
                                </div>
                                <div class="public-chart-canvas" data-public-analytics-chart="area"></div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-6">
                            <div class="public-table-panel h-100">
                                <div class="public-chart-head">
                                    <strong>Prioritas Pembinaan Agregat</strong>
                                    <small>Ringkasan area tanpa membuka data individual.</small>
                                </div>
                                <div class="public-analytics-table" data-public-analytics-table="area"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="public-analytics-narrative mt-4" data-public-analytics-narrative>
                <span>Ringkasan Wawasan</span>
                <p>Menunggu data wilayah aktif untuk menyusun ringkasan analitik.</p>
            </aside>
        </div>
    </div>
</div>