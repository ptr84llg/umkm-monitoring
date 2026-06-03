<div class="hero-board public-map-board reveal reveal-delay-1" data-tilt-card>
    <div class="card border-0 board-window public-map-window">
        <div class="card-body p-0">
            <div class="public-map-toolbar d-flex align-items-center justify-content-between gap-3">
                <div>
                    <span>Peta UMKM publik</span>
                    <strong>Kota Lubuklinggau</strong>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light public-map-select" data-region-open data-region-modal-open>
                        Semua Kecamatan
                    </button>
                    <button type="button" class="btn btn-sm btn-light public-map-filter" data-region-open data-region-modal-open aria-label="Filter wilayah">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7L4 5Z"/></svg>
                    </button>
                </div>
            </div>

            <div class="public-map-visual" role="img" aria-label="Ilustrasi peta sebaran UMKM Kota Lubuklinggau">
                <div class="map-line line-a"></div>
                <div class="map-line line-b"></div>
                <div class="map-line line-c"></div>
                <div class="map-boundary"></div>
                <button class="map-zoom zoom-plus" type="button" aria-label="Perbesar peta">+</button>
                <button class="map-zoom zoom-minus" type="button" aria-label="Perkecil peta">−</button>

                @foreach ([
                    ['x' => 22, 'y' => 28, 't' => 'mikro'], ['x' => 31, 'y' => 22, 't' => 'kecil'], ['x' => 42, 'y' => 31, 't' => 'mikro'],
                    ['x' => 53, 'y' => 20, 't' => 'kecil'], ['x' => 66, 'y' => 30, 't' => 'mikro'], ['x' => 74, 'y' => 42, 't' => 'kecil'],
                    ['x' => 29, 'y' => 45, 't' => 'kecil'], ['x' => 39, 'y' => 49, 't' => 'mikro'], ['x' => 48, 'y' => 43, 't' => 'menengah'],
                    ['x' => 57, 'y' => 51, 't' => 'mikro'], ['x' => 67, 'y' => 57, 't' => 'kecil'], ['x' => 36, 'y' => 65, 't' => 'mikro'],
                    ['x' => 49, 'y' => 69, 't' => 'kecil'], ['x' => 59, 'y' => 73, 't' => 'mikro'], ['x' => 76, 'y' => 70, 't' => 'menengah'],
                    ['x' => 20, 'y' => 64, 't' => 'mikro'], ['x' => 83, 'y' => 30, 't' => 'mikro'], ['x' => 14, 'y' => 44, 't' => 'kecil'],
                ] as $marker)
                    <span class="map-marker is-{{ $marker['t'] }}" style="--x: {{ $marker['x'] }}%; --y: {{ $marker['y'] }}%;" aria-hidden="true"></span>
                @endforeach

                <div class="public-map-popup">
                    <button type="button" class="btn-close" aria-label="Tutup"></button>
                    <div>
                        <span>Nama Usaha</span>
                        <strong>Pempek Berkah</strong>
                    </div>
                    <div>
                        <span>Kategori</span>
                        <strong>Kuliner</strong>
                    </div>
                    <div>
                        <span>Skala Usaha</span>
                        <strong>Mikro</strong>
                    </div>
                    <small>Lokasi ditampilkan secara umum untuk publik.</small>
                </div>

                <div class="public-map-legend">
                    <span><i class="is-mikro"></i>Mikro</span>
                    <span><i class="is-kecil"></i>Kecil</span>
                    <span><i class="is-menengah"></i>Menengah</span>
                </div>
            </div>

            <div class="public-map-footer d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                <span>Data agregat dan peta bersifat public-safe. Detail sensitif hanya tersedia bagi pengguna berizin.</span>
                <span data-login-mount
                      data-login-key="map-register"
                      data-login-label="Daftar UMKM Baru"
                      data-dashboard-label="Buka Ruang Kerja"
                      data-login-class="btn btn-success btn-sm public-map-cta"></span>
            </div>
        </div>
    </div>
</div>