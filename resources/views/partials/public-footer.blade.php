<footer class="umkm-public-footer py-5">
    <div class="container">
        <div class="card border-0 footer-hero mb-3">
            <div class="card-body p-4 p-xl-5">
                <div class="row align-items-xl-center g-4 g-xl-5">
                    <div class="col-12 col-lg-10 col-xl-6 mx-lg-auto mx-xl-0">
                        <div class="footer-brand-block">
                            <a class="footer-logo d-inline-flex align-items-center gap-3 text-decoration-none" href="{{ url('/') }}" aria-label="Monitoring UMKM">
                                <span>MU</span>
                                <strong>Monitoring UMKM</strong>
                            </a>

                            <p class="mb-0 mt-3">
                                Monitoring UMKM menyajikan pengelolaan data, pemantauan perkembangan,
                                visualisasi sebaran, dan ringkasan pendukung keputusan melalui tampilan
                                Visual Analitik Interaktif.
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-lg-10 col-xl-5 ms-xl-auto mx-lg-auto mx-xl-0">
                        <div class="row g-3 footer-feature-grid">
                            <div class="col-12 col-sm-4">
                                <div class="card h-100 border-0 footer-feature">
                                    <div class="card-body">
                                        <span class="footer-icon">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm3 5h8V6H8v2Zm0 5h8v-2H8v2Zm0 5h5v-2H8v2Z"/>
                                            </svg>
                                        </span>
                                        <strong>Data</strong>
                                        <small>Profil usaha tertata</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-4">
                                <div class="card h-100 border-0 footer-feature">
                                    <div class="card-body">
                                        <span class="footer-icon">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/>
                                            </svg>
                                        </span>
                                        <strong>Analitik</strong>
                                        <small>Grafik dan indikator</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-4">
                                <div class="card h-100 border-0 footer-feature">
                                    <div class="card-body">
                                        <span class="footer-icon">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/>
                                            </svg>
                                        </span>
                                        <strong>Wilayah</strong>
                                        <small>Peta sebaran</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 footer-shell">
            <div class="col-12 col-lg-10 col-xl-4 mx-lg-auto mx-xl-0">
                <div class="card h-100 border-0 footer-column">
                    <div class="card-body">
                        <div class="footer-column-title d-flex align-items-center gap-3 mb-3">
                            <span class="footer-column-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M4 5h16v3H4V5Zm0 5h16v3H4v-3Zm0 5h10v3H4v-3Z"/>
                                </svg>
                            </span>
                            <strong>Navigasi</strong>
                        </div>

                        <div class="d-grid gap-2">
                            <a class="footer-link" href="{{ url('/') }}">
                                <span class="footer-link-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 3 3 10.5V21h6v-6h6v6h6V10.5L12 3Z"/>
                                    </svg>
                                </span>
                                <span>Beranda</span>
                            </a>

                            <a class="footer-link" href="{{ url('/#dashboard') }}">
                                <span class="footer-link-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/>
                                    </svg>
                                </span>
                                <span>Dashboard</span>
                            </a>

                            <a class="footer-link" href="{{ url('/#ringkasan') }}">
                                <span class="footer-link-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M5 4h14v2H5V4Zm0 5h14v2H5V9Zm0 5h10v2H5v-2Zm0 5h7v2H5v-2Z"/>
                                    </svg>
                                </span>
                                <span>Ringkasan</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-10 col-xl-4 mx-lg-auto mx-xl-0">
                <div class="card h-100 border-0 footer-column">
                    <div class="card-body">
                        <div class="footer-column-title d-flex align-items-center gap-3 mb-3">
                            <span class="footer-column-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8h16v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 8V7a3 3 0 0 1 6 0v3H9Z"/>
                                </svg>
                            </span>
                            <strong>Akses</strong>
                        </div>

                        <div class="d-grid gap-2">
                            <a class="footer-link" href="{{ route('login') }}" data-location-gated data-location-gated-key="footer-login">
                                <span class="footer-link-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M10 17v-3H3v-4h7V7l5 5-5 5Zm2-14h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7v-2h7V5h-7V3Z"/>
                                    </svg>
                                </span>
                                <span>Masuk Sistem</span>
                            </a>

                            <a class="footer-link" href="{{ url('/#cta') }}">
                                <span class="footer-link-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M13 5h8v2h-8V5ZM3 4h8v8H3V4Zm2 2v4h4V6H5Zm8 4h8v2h-8v-2Zm0 5h8v2h-8v-2ZM3 14h8v6H3v-6Zm2 2v2h4v-2H5Z"/>
                                    </svg>
                                </span>
                                <span>Mulai Penggunaan</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-10 col-xl-4 mx-lg-auto mx-xl-0">
                <div class="card h-100 border-0 footer-column footer-note">
                    <div class="card-body">
                        <div class="footer-column-title d-flex align-items-center gap-3 mb-3">
                            <span class="footer-column-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M11 17h2v-6h-2v6Zm0-8h2V7h-2v2Zm1-7a10 10 0 1 0 0 20 10 10 0 0 0 0-20Z"/>
                                </svg>
                            </span>
                            <strong>Informasi</strong>
                        </div>

                        <p class="mb-0">
                            Tampilan publik ini menyediakan gambaran umum sistem. Akses data dan dashboard internal
                            dilakukan melalui akun yang terdaftar agar pemantauan tetap terkontrol dan aman.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-md-between gap-2 mt-4">
            <span>&copy; {{ date('Y') }} Monitoring UMKM.</span>
            <span>Visual Analitik Interaktif</span>
        </div>
    </div>
</footer>