<footer class="umkm-public-footer">
    <div class="container">
        <div class="footer-shell">
            <div class="footer-brand">
                <a class="footer-logo" href="{{ url('/') }}" aria-label="UMKM Monitoring">
                    <span>MU</span>
                    <strong>UMKM Monitoring</strong>
                </a>
                <p>
                    Sistem informasi UMKM untuk pengelolaan data, pemantauan perkembangan,
                    visualisasi sebaran, dan penyajian ringkasan pendukung keputusan.
                </p>
            </div>

            <div class="footer-links">
                <div>
                    <strong>Navigasi</strong>
                    <a href="{{ url('/') }}">Beranda</a>
                    <a href="{{ url('/#dashboard') }}">Dashboard</a>
                    <a href="{{ url('/#modul') }}">Modul</a>
                </div>

                <div>
                    <strong>Akses</strong>
                    <a href="{{ route('login') }}">Masuk Sistem</a>
                    <a href="{{ url('/#ringkasan') }}">Ringkasan</a>
                    <a href="{{ url('/#cta') }}">Mulai</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} UMKM Monitoring.</span>
            <span>Sistem Informasi UMKM</span>
        </div>
    </div>
</footer>
