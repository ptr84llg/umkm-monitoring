@php
    // DOM awal tidak membawa data peta riil. Data peta/cluster diisi setelah AJAX wilayah aktif berhasil.
    $publicLandingMap = [];
@endphp

<div class="hero-board public-map-board reveal reveal-delay-1" data-tilt-card>
    <div class="card border-0 board-window public-map-window">
        <div class="card-body p-0">
            <div class="public-map-toolbar d-flex align-items-center justify-content-between gap-3">
                <div>
                    <span>Peta Sebaran UMKM</span>
                    <strong data-public-context-label>Belum dimuat</strong>
                </div>
                <div class="d-flex align-items-center gap-2 public-map-toolbar-actions" data-public-region-action-mount="map-toolbar" hidden aria-hidden="true"></div>
            </div>
            <div class="public-map-visual public-region-map-visual"
                 data-public-google-region-map
                 data-map-provider="google"
                 data-interaction-ready="false"
                 data-google-maps-key="{{ (string) config('umkm.map.google_maps.api_key') }}"
                 data-google-maps-map-id="{{ (string) config('umkm.map.google_maps.map_id') }}"
                 data-region-map-geometry-url="{{ url('/api/public/landing-region-map/geometry') }}"
                 role="application"
                 aria-label="Peta wilayah aktif UMKM Kota Lubuklinggau">
                <div class="public-region-map-canvas" data-public-google-region-map-canvas></div>

                <div class="public-region-map-hover-panel" data-public-region-map-hover-panel data-visible="false" hidden aria-live="polite">
                    <span data-public-region-map-hover-level>Wilayah</span>
                    <strong data-public-region-map-hover-title>Arahkan kursor ke wilayah</strong>
                    <small><b data-public-region-map-hover-total>0</b> UMKM tercatat</small>
                    <em>Klik wilayah untuk melihat datanya</em>
                </div>

                <div class="public-map-legend public-region-map-legend" data-public-region-map-legend data-ready="false" aria-label="Legenda jumlah UMKM">
                    <span><i class="is-density-empty"></i>Tanpa UMKM</span>
                    <span><i class="is-empty-village-warning"></i>Ada kelurahan tanpa UMKM</span>
                    <span><i class="is-density-low"></i>Jumlah sedikit</span>
                    <span><i class="is-density-medium"></i>Jumlah sedang</span>
                    <span><i class="is-density-high"></i>Jumlah banyak</span>
                    <span><i class="is-active"></i>Wilayah aktif</span>
                </div>
            </div>

            <div class="public-map-footer public-region-map-footer d-flex flex-column flex-xl-row align-items-xl-center justify-content-xl-between gap-3">
                <div class="public-region-map-footer-main">
                    <span class="public-region-map-state" data-public-region-map-state data-tone="info">
                        Menunggu data wilayah aktif.
                    </span>
                    <strong data-public-region-map-title>Belum dimuat</strong>
                    <small data-public-region-map-scope>Kota/Kecamatan/Kelurahan</small>
                </div>
                <div class="public-region-map-meta" aria-label="Ringkasan peta wilayah aktif">
                    <span><b data-public-region-map-feature-count>0</b> wilayah tampil</span>
                    <span>Tampilan <b data-public-region-map-visible-level>Wilayah</b></span>
                    <span><b data-public-region-map-total-umkm>0</b> UMKM tercatat</span>
                    <span>Total <b data-public-region-map-operational-total>0</b> UMKM</span>
                    <span class="d-none" data-public-region-map-unmatched-wrap><b data-public-region-map-unmatched>0</b> belum tercatat pada wilayah</span>
                    <span>Jumlah terbanyak <b data-public-region-map-density-max>0</b> UMKM/wilayah</span>
                </div>
            </div>
        </div>
    </div>
</div>
