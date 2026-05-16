<footer class="umkm-public-footer">
    <div class="container">
        <div class="footer-hero">
            <div class="footer-brand-block">
                <a class="footer-logo" href="{{ url('/') }}" aria-label="UMKM Monitoring">
                    <span>MU</span>
                    <strong>UMKM Monitoring</strong>
                </a>
                <p>
                    Sistem informasi UMKM untuk pengelolaan data, pemantauan perkembangan,
                    visualisasi sebaran, dan penyajian ringkasan pendukung keputusan.
                </p>
            </div>

            <div class="footer-feature-grid">
                <div class="footer-feature">
                    <span class="footer-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm3 5h8V6H8v2Zm0 5h8v-2H8v2Zm0 5h5v-2H8v2Z"/>
                        </svg>
                    </span>
                    <strong>Data</strong>
                    <small>Profil usaha tertata</small>
                </div>

                <div class="footer-feature">
                    <span class="footer-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 19h16v2H4v-2Zm2-2V9h3v8H6Zm5 0V4h3v13h-3Zm5 0v-6h3v6h-3Z"/>
                        </svg>
                    </span>
                    <strong>Analitik</strong>
                    <small>Grafik dan indikator</small>
                </div>

                <div class="footer-feature">
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

        <div class="footer-shell">
            <div class="footer-column">
                <strong>Navigasi</strong>
                <a href="{{ url('/') }}">Beranda</a>
                <a href="{{ url('/#dashboard') }}">Dashboard</a>
                <a href="{{ url('/#modul') }}">Modul Sistem</a>
                <a href="{{ url('/#ringkasan') }}">Ringkasan</a>
            </div>

            <div class="footer-column">
                <strong>Akses</strong>
                <a href="{{ route('login') }}">Masuk Sistem</a>
                <a href="{{ url('/#cta') }}">Mulai Penggunaan</a>
            </div>

            <div class="footer-column footer-note">
                <strong>Informasi</strong>
                <p>
                    Tampilan publik ini menyediakan gambaran umum sistem. Akses data dan dashboard internal
                    dilakukan melalui akun yang terdaftar.
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} UMKM Monitoring.</span>
            <span>Sistem Informasi UMKM</span>
        </div>
    </div>
</footer>
