@props([
    'id' => 'landingRegionModal',
    'titleId' => 'landingRegionModalTitle',
    'mode' => 'analytics',
    'title' => 'Pilih Wilayah Preview',
    'description' => 'Wilayah pada landing dikunci untuk Sumatera Selatan dan Kota Lubuklinggau. Data yang tampil bersifat agregat/preview dan tidak menampilkan data sensitif.',
    'currentLabel' => 'Kota Lubuklinggau',
    'applyLabel' => 'Terapkan Wilayah',
    'cancelLabel' => 'Batal',
    'provinceCode' => '16',
    'provinceName' => 'Sumatera Selatan',
    'cityCode' => '16.73',
    'cityName' => 'Kota Lubuklinggau',
    'provinceLocked' => true,
    'cityLocked' => true,
])

@php
    $mode = trim((string) $mode) ?: 'analytics';
    $provinceDisabled = $provinceLocked ? 'disabled' : null;
    $cityDisabled = $cityLocked ? 'disabled' : null;
@endphp

<div {{ $attributes->class(['modal', 'fade', 'landing-region-modal-shell'])->merge([
    'id' => $id,
    'tabindex' => '-1',
    'aria-labelledby' => $titleId,
    'aria-hidden' => 'true',
    'data-region-modal' => true,
    'data-region-mode' => $mode,
]) }}>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable landing-region-dialog">
        <section class="modal-content landing-region-modal">
            <div class="modal-header landing-region-modal-head">
                <span class="region-modal-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2.75A7.25 7.25 0 0 0 4.75 10c0 5.15 7.25 11.25 7.25 11.25S19.25 15.15 19.25 10A7.25 7.25 0 0 0 12 2.75Zm0 9.65a2.4 2.4 0 1 1 0-4.8 2.4 2.4 0 0 1 0 4.8Z"/>
                    </svg>
                </span>

                <div class="region-modal-copy">
                    <h5 class="modal-title" id="{{ $titleId }}">{{ $title }}</h5>
                    <p class="mb-0">{{ $description }}</p>
                </div>

                <button type="button"
                        class="btn-close landing-region-modal-close"
                        data-bs-dismiss="modal"
                        data-region-modal-close
                        aria-label="Tutup pilihan wilayah"></button>
            </div>

            <div class="modal-body landing-region-modal-body">
                <div class="alert landing-region-alert" role="alert" data-region-modal-alert hidden></div>

                <div class="row g-3 landing-region-form">
                    <div class="col-12 col-md-6 landing-region-field">
                        <label for="landingProvinceSelect" class="form-label">Provinsi</label>
                        <select id="landingProvinceSelect"
                                class="form-select"
                                data-landing-region-province
                                @if($provinceDisabled) disabled @endif>
                            <option value="{{ $provinceCode }}">{{ $provinceName }}</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 landing-region-field">
                        <label for="landingCitySelect" class="form-label">Kabupaten/Kota</label>
                        <select id="landingCitySelect"
                                class="form-select"
                                data-landing-region-city
                                @if($cityDisabled) disabled @endif>
                            <option value="{{ $cityCode }}">{{ $cityName }}</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 landing-region-field">
                        <label for="landingDistrictSelect" class="form-label">Kecamatan</label>
                        <select id="landingDistrictSelect" class="form-select" data-landing-region-district>
                            <option value="">Memuat kecamatan...</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 landing-region-field">
                        <label for="landingVillageSelect" class="form-label">Desa/Kelurahan</label>
                        <select id="landingVillageSelect" class="form-select" data-landing-region-village>
                            <option value="__ALL_VILLAGES__">Semua Kelurahan</option>
                        </select>
                    </div>
                </div>

                <div class="card border-0 landing-region-current">
                    <div class="card-body">
                        <span>Konteks saat ini</span>
                        <strong data-region-modal-current>{{ $currentLabel }}</strong>
                    </div>
                </div>
            </div>

            <div class="modal-footer landing-region-modal-actions">
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                        data-region-modal-close>{{ $cancelLabel }}</button>

                <button type="button"
                        class="btn btn-primary"
                        data-region-modal-apply>{{ $applyLabel }}</button>
            </div>
        </section>
    </div>
</div>
