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

            <div class="public-map-visual" role="img" aria-label="Ilustrasi peta sebaran UMKM Kota Lubuklinggau">
                <div class="map-line line-a"></div>
                <div class="map-line line-b"></div>
                <div class="map-line line-c"></div>
                <div class="map-boundary"></div>
                <button class="map-zoom zoom-plus" type="button" aria-label="Perbesar peta">+</button>
                <button class="map-zoom zoom-minus" type="button" aria-label="Perkecil peta">−</button>
                <span class="map-marker is-mikro marker-pos-01" aria-hidden="true"></span>
                <span class="map-marker is-kecil marker-pos-02" aria-hidden="true"></span>
                <span class="map-marker is-mikro marker-pos-03" aria-hidden="true"></span>
                <span class="map-marker is-kecil marker-pos-04" aria-hidden="true"></span>
                <span class="map-marker is-mikro marker-pos-05" aria-hidden="true"></span>
                <span class="map-marker is-kecil marker-pos-06" aria-hidden="true"></span>
                <span class="map-marker is-kecil marker-pos-07" aria-hidden="true"></span>
                <span class="map-marker is-mikro marker-pos-08" aria-hidden="true"></span>
                <span class="map-marker is-menengah marker-pos-09" aria-hidden="true"></span>
                <span class="map-marker is-mikro marker-pos-10" aria-hidden="true"></span>
                <span class="map-marker is-kecil marker-pos-11" aria-hidden="true"></span>
                <span class="map-marker is-mikro marker-pos-12" aria-hidden="true"></span>
                <span class="map-marker is-kecil marker-pos-13" aria-hidden="true"></span>
                <span class="map-marker is-mikro marker-pos-14" aria-hidden="true"></span>
                <span class="map-marker is-menengah marker-pos-15" aria-hidden="true"></span>
                <span class="map-marker is-mikro marker-pos-16" aria-hidden="true"></span>
                <span class="map-marker is-mikro marker-pos-17" aria-hidden="true"></span>
                <span class="map-marker is-kecil marker-pos-18" aria-hidden="true"></span>
                <span class="map-zone-label zone-a">Timur</span>
                <span class="map-zone-label zone-b">Barat</span>
                <span class="map-zone-label zone-c">Utara</span>
                <span class="map-zone-label zone-d">Selatan</span>

                @foreach (($publicLandingMap['clusters'] ?? []) as $cluster)
                    <span class="map-cluster {{ $cluster['class'] ?? '' }}" data-public-map-cluster="{{ $loop->index }}" aria-hidden="true"><strong data-public-map-cluster-value>{{ $cluster['value'] ?? '—' }}</strong></span>
                @endforeach

                <div class="public-map-legend">
                    <span><i class="is-mikro"></i>Mikro</span>
                    <span><i class="is-kecil"></i>Kecil</span>
                    <span><i class="is-menengah"></i>Menengah</span>
                </div>
            </div>

            <div class="public-map-footer d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                <span>{{ $publicLandingMap['note'] ?? 'Data peta belum dimuat. Menunggu konteks wilayah aktif.' }}</span>
            </div>
        </div>
    </div>
</div>
