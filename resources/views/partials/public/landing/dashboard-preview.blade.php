
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
        
