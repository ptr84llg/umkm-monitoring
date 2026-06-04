@props([
    'id' => 'landingLocationGateModal',
    'titleId' => 'landingLocationGateTitle',
])

<div class="modal fade location-gate-shell landing-core-modal"
     id="{{ $id }}"
     tabindex="-1"
     aria-labelledby="{{ $titleId }}"
     aria-hidden="true"
     data-location-gate-notice>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <section class="modal-content border-0 shadow-lg rounded-4 location-gate-core-card">
            <div class="modal-header border-0 pb-0 location-gate-core-head">
                <span class="location-gate-core-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/>
                    </svg>
                </span>

                <div class="location-gate-core-copy">
                    <h5 class="modal-title" id="{{ $titleId }}" data-location-gate-title>Memeriksa akses lokasi</h5>
                    <p class="mb-0" data-location-gate-message>
                        Sistem sedang memeriksa status lokasi untuk membuka akses masuk.
                    </p>

                    <div class="location-gate-permission" data-location-permission-state hidden>
                        Status izin lokasi: <strong data-location-permission-label>memeriksa</strong>
                    </div>
                </div>

                <button type="button"
                        class="btn-close location-gate-close"
                        data-location-gate-close
                        aria-label="Tutup pemberitahuan lokasi"></button>
            </div>

            <div class="modal-body location-gate-core-body">
                <div class="location-gate-summary" data-location-info hidden>
                    <div class="row g-2">
                        <div class="col-12 col-sm-4">
                            <div class="card border-0 location-info-card">
                                <div class="card-body">
                                    <span>Status</span>
                                    <strong data-location-info-status>Memeriksa</strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-4">
                            <div class="card border-0 location-info-card">
                                <div class="card-body">
                                    <span>Akurasi</span>
                                    <strong data-location-info-accuracy>Belum tersedia</strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-4">
                            <div class="card border-0 location-info-card">
                                <div class="card-body">
                                    <span>Waktu Cek</span>
                                    <strong data-location-info-checked-at>Belum tersedia</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="collapse location-gate-technical mt-3" id="landingLocationTechnicalDetail">
                        <div class="card border-0 location-technical-card">
                            <div class="card-body">
                                <div class="location-technical-list">
                                    <div>
                                        <span>Koordinat</span>
                                        <strong data-location-info-coordinate>Belum tersedia</strong>
                                    </div>
                                    <div>
                                        <span>IP</span>
                                        <strong data-location-info-ip>Belum tersedia</strong>
                                    </div>
                                    <div>
                                        <span>Perangkat</span>
                                        <strong data-location-info-device>Belum tersedia</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="location-gate-guide" data-location-guide hidden>
                    <div class="alert location-guide-note mb-3" role="note">
                        <strong>Cara mengaktifkan ulang izin lokasi</strong>
                        <p class="mb-0">Ikuti langkah berikut sesuai tampilan browser yang digunakan.</p>
                    </div>

                    <div class="row g-2 location-guide-steps">
                        <div class="col-12">
                            <div class="card border-0 location-guide-step">
                                <div class="card-body d-flex align-items-start gap-3">
                                    <span>01</span>
                                    <p>Klik ikon kunci, ikon informasi, atau ikon pengaturan di sebelah kiri alamat website.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 location-guide-step">
                                <div class="card-body d-flex align-items-start gap-3">
                                    <span>02</span>
                                    <p>Pilih <strong>Site settings</strong> atau <strong>Setelan situs</strong>.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 location-guide-step">
                                <div class="card-body d-flex align-items-start gap-3">
                                    <span>03</span>
                                    <p>Cari bagian <strong>Location</strong> atau <strong>Lokasi</strong>.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 location-guide-step">
                                <div class="card-body d-flex align-items-start gap-3">
                                    <span>04</span>
                                    <p>Ubah menjadi <strong>Allow</strong>, <strong>Izinkan</strong>, atau <strong>Ask</strong>.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 location-guide-step">
                                <div class="card-body d-flex align-items-start gap-3">
                                    <span>05</span>
                                    <p>Refresh halaman, lalu klik <strong>Cek ulang lokasi</strong>.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 location-gate-actions location-gate-core-actions">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-bs-toggle="collapse"
                        data-bs-target="#landingLocationTechnicalDetail"
                        aria-expanded="false"
                        aria-controls="landingLocationTechnicalDetail">
                    <span>Detail perangkat</span>
                </button>

                <button type="button" class="btn btn-outline-secondary btn-sm location-gate-guide-toggle" data-location-guide-toggle>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M11 18h2v-2h-2v2Zm1-16a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16Zm0-14a3.25 3.25 0 0 0-3.25 3.25h2A1.25 1.25 0 1 1 12 10.5c-1.5 0-2.75 1.25-2.75 2.75V14h2v-.75c0-.4.35-.75.75-.75a3.25 3.25 0 0 0 0-6.5Z"/>
                    </svg>
                    <span>Izin lokasi</span>
                </button>

                <button type="button" class="btn btn-primary btn-sm location-gate-retry" data-location-retry>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M17.65 6.35A7.95 7.95 0 0 0 12 4a8 8 0 1 0 7.45 5.1h-2.2A6 6 0 1 1 12 6c1.66 0 3.14 .69 4.22 1.78L13 11h8V3l-3.35 3.35Z"/>
                    </svg>
                    <span>Cek ulang</span>
                </button>
            </div>
        </section>
    </div>
</div>