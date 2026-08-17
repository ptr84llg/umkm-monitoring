@extends('layouts.dashboard')

@section('title', 'Analitik Wilayah dan Peta Internal')

@section('content')
@php
    $filters = $data['filters'] ?? [];
    $options = $data['filter_options'] ?? [];
    $summary = $data['summary'] ?? [];
    $map = $data['map'] ?? [];
    $regionRows = $data['region_rows'] ?? [];
    $freshness = $data['freshness'] ?? [];

    $label = static fn ($value): string => $value
        ? \Illuminate\Support\Str::headline((string) $value)
        : 'Belum tersedia';

    $baseFilter = array_filter(
        $filters,
        static fn ($value) => $value !== null && $value !== ''
    );

    $mapPayload = [
        'geometry' => $map['geometry'] ?? [
            'type' => 'FeatureCollection',
            'features' => [],
        ],
        'points' => $map['points'] ?? [],
        'coordinateAccess' => (bool) ($map['coordinate_access'] ?? false),
        'visibleLevel' => $map['visible_level'] ?? 'district',
    ];
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border shadow-sm">
        <div class="card-body p-4 d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-2">
                    Internal Spatial Analytics
                </span>
                <h1 class="h3 mb-2">Analitik Wilayah & Peta Internal</h1>
                <p class="text-body-secondary mb-0">
                    Peta administratif memperlihatkan distribusi UMKM berdasarkan wilayah.
                    Titik koordinat individual hanya tersedia jika pengguna memiliki izin koordinat sensitif.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 align-self-xl-start">
                <a href="{{ route('admin-dinas.analytics.index', $baseFilter) }}" class="btn btn-outline-primary">
                    Analitik Umum
                </a>
                <a href="{{ route('admin-dinas.analytics.decision', $baseFilter) }}" class="btn btn-dark">
                    Analitik Keputusan
                </a>
                <a href="{{ route('admin-dinas.umkm.index', $baseFilter) }}" class="btn btn-outline-secondary">
                    Data UMKM
                </a>
                @if($data['can_view_financial'] ?? false)
                    <a href="{{ route('admin-dinas.analytics.financial', $baseFilter) }}" class="btn btn-warning">
                        Ekonomi & Keuangan
                    </a>
                @endif
            </div>
        </div>
    </section>

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Konteks Peta</h2>
                    <p class="text-body-secondary mb-0">
                        Peta dan tabel wilayah mengikuti filter analitik yang sama.
                    </p>
                </div>
                <a href="{{ route('admin-dinas.analytics.spatial') }}" class="btn btn-sm btn-outline-secondary align-self-start">
                    Reset Filter
                </a>
            </div>

            <form method="GET" action="{{ route('admin-dinas.analytics.spatial') }}" class="row g-3">
                <div class="col-md-6 col-xl">
                    <label class="form-label" for="district_id">Kecamatan</label>
                    <select class="form-select" id="district_id" name="district_id">
                        <option value="">Semua Kecamatan</option>
                        @foreach(($options['districts'] ?? []) as $item)
                            <option value="{{ $item->id }}" data-region-code="{{ $item->code ?? '' }}" data-parent-code="{{ $item->parent_code ?? '' }}" @selected((string)($filters['district_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl">
                    <label class="form-label" for="village_id">Kelurahan</label>
                    <select class="form-select" id="village_id" name="village_id">
                        <option value="">Semua Kelurahan</option>
                        @foreach(($options['villages'] ?? []) as $item)
                            <option value="{{ $item->id }}" data-region-code="{{ $item->code ?? '' }}" data-parent-code="{{ $item->parent_code ?? '' }}" @selected((string)($filters['village_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl">
                    <label class="form-label" for="category_id">Kategori</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Semua Kategori</option>
                        @foreach(($options['categories'] ?? []) as $item)
                            <option value="{{ $item->id }}" data-region-code="{{ $item->code ?? '' }}" data-parent-code="{{ $item->parent_code ?? '' }}" @selected((string)($filters['category_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl">
                    <label class="form-label" for="type_id">Jenis Usaha</label>
                    <select class="form-select" id="type_id" name="type_id">
                        <option value="">Semua Jenis</option>
                        @foreach(($options['types'] ?? []) as $item)
                            <option value="{{ $item->id }}" data-region-code="{{ $item->code ?? '' }}" data-parent-code="{{ $item->parent_code ?? '' }}" @selected((string)($filters['type_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl">
                    <label class="form-label" for="marketing_method_id">Pemasaran</label>
                    <select class="form-select" id="marketing_method_id" name="marketing_method_id">
                        <option value="">Semua Metode</option>
                        @foreach(($options['marketingMethods'] ?? []) as $item)
                            <option value="{{ $item->id }}" data-region-code="{{ $item->code ?? '' }}" data-parent-code="{{ $item->parent_code ?? '' }}" @selected((string)($filters['marketing_method_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-8 col-xl">
                    <label class="form-label" for="quality_status">Mutu Data</label>
                    <select class="form-select" id="quality_status" name="quality_status">
                        <option value="">Semua</option>
                        @foreach(($options['qualityStatuses'] ?? []) as $value)
                            <option value="{{ $value }}" @selected((string)($filters['quality_status'] ?? '') === (string)$value)>
                                {{ $label($value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-xl-auto d-flex align-items-end">
                    <button class="btn btn-primary px-4" type="submit">Terapkan</button>
                </div>
            </form>
        </div>
    </section>

    @include('pages.admin-dinas.partials.active-context', [
        'contextFilters' => $filters,
        'contextOptions' => $options,
        'contextCount' => $summary['total_umkm'] ?? 0,
    ])

    <section class="row g-3">
        @foreach([
            ['UMKM dalam konteks', $summary['total_umkm'] ?? 0, 'Record operasional sesuai filter'],
            ['Terasosiasi administratif', $summary['administrative_associated'] ?? 0, 'Memiliki asosiasi kecamatan'],
            ['Belum terasosiasi', $summary['administrative_unassociated'] ?? 0, 'Tidak dipaksakan ke wilayah'],
            ['Coordinate-mapped', $summary['coordinate_mapped'] ?? 0, 'Status terpetakan + latitude + longitude'],
            ['Cakupan coordinate-mapped', number_format((float)($summary['coordinate_mapped_percent'] ?? 0), 2, ',', '.') . '%', 'Bukan cakupan asosiasi administratif'],
        ] as $metric)
            <div class="col-md-6 col-xl">
                <div class="card border shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-body-secondary">{{ $metric[0] }}</div>
                        <div class="h2 mb-1">
                            @if(is_numeric($metric[1]))
                                {{ number_format((int)$metric[1], 0, ',', '.') }}
                            @else
                                {{ $metric[1] }}
                            @endif
                        </div>
                        <div class="small text-body-secondary">{{ $metric[2] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="alert alert-info mb-0">
        <strong>Asosiasi administratif berbeda dari titik koordinat.</strong>
        UMKM yang terhubung ke kecamatan atau kelurahan tidak otomatis dianggap mempunyai titik lokasi presisi.
        Titik individual hanya memenuhi rule jika <code>coordinate_status = terpetakan</code>,
        latitude terisi, dan longitude terisi.
    </section>

    <section class="row g-4">
        <div class="col-xl-8">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Peta Administratif Interaktif</h2>
                            <p class="text-body-secondary mb-0">
                                Warna wilayah mengikuti indikator yang dipilih. Klik wilayah untuk melihat detail ringkas dan membuka Data UMKM.
                            </p>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-end">
                            <div>
                                <label class="form-label small mb-1" for="spatialMetric">Indikator</label>
                                <select class="form-select form-select-sm" id="spatialMetric">
                                    <option value="umkm_total">Jumlah UMKM</option>
                                    <option value="workers_total">Tenaga Kerja</option>
                                    <option value="quality_affected">UMKM dengan Flag Mutu</option>
                                    @if($data['can_view_financial'] ?? false)
                                        <option value="financial_filled">Cakupan Data Keuangan</option>
                                    @endif
                                </select>
                            </div>

                            @if($data['can_view_coordinates'] ?? false)
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" value="1" id="spatialPointToggle">
                                    <label class="form-check-label small" for="spatialPointToggle">
                                        Tampilkan titik koordinat
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="border rounded-3 bg-body-tertiary overflow-hidden">
                        <svg
                            id="adminDinasSpatialMap"
                            viewBox="0 0 1000 620"
                            role="img"
                            aria-label="Peta administratif UMKM Kota Lubuk Linggau"
                            style="display:block; width:100%; min-height:520px;"
                        ></svg>
                    </div>

                    <div class="d-flex flex-wrap gap-3 mt-3 small text-body-secondary">
                        <span><strong>Lebih gelap</strong> = nilai indikator lebih tinggi</span>
                        <span>Geometry berasal dari GeoJSON lokal sistem</span>
                        @if($data['can_view_coordinates'] ?? false)
                            <span>Titik biru = UMKM yang memenuhi rule coordinate-mapped</span>
                        @else
                            <span><strong>Titik individual disembunyikan</strong> karena izin koordinat sensitif tidak aktif</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-4">
                    <span class="badge text-bg-success-subtle text-success-emphasis mb-2">Detail Wilayah</span>
                    <h2 class="h5" id="spatialRegionName">
                        Pilih wilayah pada peta
                    </h2>

                    <dl class="row mb-3">
                        <dt class="col-7">UMKM</dt>
                        <dd class="col-5 text-end" id="spatialRegionUmkm">—</dd>

                        <dt class="col-7">Tenaga Kerja</dt>
                        <dd class="col-5 text-end" id="spatialRegionWorkers">—</dd>

                        <dt class="col-7">UMKM dengan Flag</dt>
                        <dd class="col-5 text-end" id="spatialRegionQuality">—</dd>

                        @if($data['can_view_financial'] ?? false)
                            <dt class="col-7">Data Keuangan Terisi</dt>
                            <dd class="col-5 text-end" id="spatialRegionFinancial">—</dd>
                        @endif

                        <dt class="col-7">Kategori Dominan</dt>
                        <dd class="col-5 text-end" id="spatialRegionCategory">—</dd>
                    </dl>

                    <a
                        id="spatialRegionDataLink"
                        class="btn btn-outline-primary w-100 disabled"
                        href="#"
                        aria-disabled="true"
                    >
                        Buka Data UMKM Wilayah
                    </a>

                    <hr>

                    <div class="small text-body-secondary">
                        <div><strong>Level peta:</strong> {{ $label($map['visible_level'] ?? 'district') }}</div>
                        <div><strong>Snapshot:</strong> {{ $freshness['snapshot_id'] ?? 'Belum tersedia' }}</div>
                        <div><strong>Terakhir sinkron:</strong> {{ $freshness['label'] ?? 'Belum tersedia' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Ringkasan Wilayah pada Peta</h2>
                    <p class="text-body-secondary mb-0">
                        Tabel menggunakan unit wilayah yang sama dengan geometry yang sedang ditampilkan.
                    </p>
                </div>
                <span class="badge text-bg-secondary align-self-lg-start">
                    {{ count($regionRows) }} wilayah
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Wilayah</th>
                            <th class="text-end">UMKM</th>
                            <th class="text-end">Tenaga Kerja</th>
                            <th class="text-end">Flag Mutu</th>
                            @if($data['can_view_financial'] ?? false)
                                <th class="text-end">Data Keuangan</th>
                            @endif
                            <th>Kategori Dominan</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($regionRows as $row)
                            @php
                                $params = $baseFilter;

                                if (($row['level'] ?? '') === 'village') {
                                    $params['village_id'] = $row['id'];
                                } else {
                                    $params['district_id'] = $row['id'];
                                }
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $row['name'] }}</div>
                                    <div class="small text-body-secondary">{{ $row['code'] }}</div>
                                </td>
                                <td class="text-end">{{ number_format((int)$row['total_umkm'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int)$row['workers_total'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int)$row['quality_affected'], 0, ',', '.') }}</td>
                                @if($data['can_view_financial'] ?? false)
                                    <td class="text-end">{{ number_format((int)$row['financial_filled'], 0, ',', '.') }}</td>
                                @endif
                                <td>{{ $row['dominant_category'] ?: 'Belum tersedia' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin-dinas.umkm.index', $params) }}">
                                        Drill-down
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ ($data['can_view_financial'] ?? false) ? 7 : 6 }}" class="text-body-secondary">
                                    Geometry atau data wilayah belum tersedia pada konteks aktif.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<script
    id="adminDinasSpatialPayload"
    type="application/json"
>{!! json_encode(
    $mapPayload,
    JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
) !!}</script>
<script src="{{ asset('assets/js/pages/admin-dinas/spatial-analytics.js') }}" defer></script>
<script src="{{ asset('assets/js/pages/admin-dinas/admin-dinas-region-cascade.js') }}" defer></script>
@endsection
