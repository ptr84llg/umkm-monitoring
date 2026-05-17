<div class="hero-board reveal reveal-delay-1" data-tilt-card>
                                    <div class="card border-0 board-source board-source-stacked mb-3" data-public-region-current>
                                        <div class="card-body p-3">
                                            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-3">
                                                <div class="board-source-info">
                                                    <span class="board-source-kicker">Wilayah aktif</span>
                                                    <strong data-public-region-source>Kota Lubuklinggau</strong>
                                                </div>

                                                <button type="button" class="btn btn-primary board-region-button" data-region-open data-region-modal-open>
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
