@php
    // DOM awal tidak membawa data peta riil. Data peta/cluster diisi setelah AJAX wilayah aktif berhasil.
    $publicLandingMap = [];
@endphp

<div class="hero-board public-map-board reveal reveal-delay-1" data-tilt-card>
    <div class="card border-0 board-window public-map-window">
        <div class="card-body p-0">
            <div class="public-map-toolbar d-flex align-items-center justify-content-between gap-3">
                <div>
                    <span>Peta Sebaran publik</span>
                    <strong data-public-context-label>Belum dimuat</strong>
                </div>
                <div class="d-flex align-items-center gap-2 public-map-toolbar-actions" data-public-region-action-mount="map-toolbar" hidden aria-hidden="true"></div>
            </div>
            <div class="public-map-visual public-region-map-visual"
                 data-public-google-region-map
                 data-map-provider="google"
                 data-google-maps-key="{{ (string) config('umkm.map.google_maps.api_key') }}"
                 data-google-maps-map-id="{{ (string) config('umkm.map.google_maps.map_id') }}"
                 data-region-map-geometry-url="{{ url('/api/public/landing-region-map/geometry') }}"
                 role="application"
                 aria-label="Peta wilayah aktif UMKM Kota Lubuklinggau">
                <div class="public-region-map-canvas" data-public-google-region-map-canvas></div>

                <div class="public-region-map-state" data-public-region-map-state data-tone="info">
                    Menunggu data wilayah aktif.
                </div>

                <aside class="public-region-map-panel" aria-label="Ringkasan peta wilayah aktif">
                    <span>Peta wilayah aktif</span>
                    <strong data-public-region-map-title>Belum dimuat</strong>
                    <small data-public-region-map-scope>Kota/Kecamatan/Kelurahan</small>
                    <div class="public-region-map-meta">
                        <span><b data-public-region-map-feature-count>0</b> wilayah tampil</span>
                        <span>Layer <b data-public-region-map-visible-level>Wilayah</b></span>
                    </div>
                </aside>

                <div class="public-map-legend public-region-map-legend">
                    <span><i class="is-district"></i>Kecamatan</span>
                    <span><i class="is-village"></i>Kelurahan</span>
                    <span><i class="is-active"></i>Wilayah aktif</span>
                </div>
            </div>

            <div class="public-map-footer d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                <span>{{ $publicLandingMap['note'] ?? 'Data peta belum dimuat. Menunggu konteks wilayah aktif.' }}</span>
            </div>
        </div>
    </div>
</div>
