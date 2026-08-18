@extends('layouts.dashboard')

@section('title', 'Perbandingan & Potensi UMKM')

@section('content')
@php
    $filters = $data['filters'] ?? [];
    $options = $data['filter_options'] ?? [];
    $summary = $data['summary'] ?? [];
    $mode = $data['analysis_mode'] ?? [
        'key' => 'citywide',
        'label' => 'Seluruh Kota',
        'title' => 'Ringkasan UMKM Seluruh Kota',
        'description' => 'Ringkasan berdasarkan data yang tersedia pada pilihan saat ini.',
    ];
    $modeKey = (string)($mode['key'] ?? 'citywide');
    $selectedType = $data['selected_type'] ?? null;
    $selectedDistrict = $data['selected_district'] ?? null;
    $selectedVillage = $data['selected_village'] ?? null;
    $competition = collect($data['competition_by_district'] ?? []);
    $competitionSummary = $data['competition_summary'] ?? [];
    $opportunities = collect($data['opportunity_types'] ?? []);
    $opportunitySummary = $data['opportunity_summary'] ?? [];
    $citywide = $data['citywide'] ?? [];
    $typeRanking = collect($citywide['type_ranking'] ?? []);
    $districtRanking = collect($citywide['district_ranking'] ?? []);
    $matrix = $citywide['distribution_matrix'] ?? [];
    $matrixDistricts = collect($matrix['districts'] ?? []);
    $matrixRows = collect($matrix['rows'] ?? []);
    $potentialPairs = collect($citywide['potential_pairs'] ?? []);
    $structuralGaps = collect($citywide['structural_gaps'] ?? []);
    $microSpatial = $data['micro_spatial'] ?? [];
    $microRows = collect($microSpatial['rows'] ?? []);
    $insights = $data['decision_insights'] ?? [];
    $minimumGroupSize = (int) data_get($data, 'methodology.minimum_group_size', 3);
    $baseFilter = array_filter(
        $filters,
        static fn ($value) => $value !== null && $value !== ''
    );

    $money = static function ($value): string {
        if ($value === null || $value === '') {
            return 'Belum tersedia';
        }

        return 'Rp' . number_format((float) $value, 0, ',', '.');
    };

    $economicMedian = static function (array $row) use ($money): string {
        if (! data_get($row, 'economic.visible', false)) {
            return 'Belum cukup / dibatasi';
        }

        return $money(data_get($row, 'economic.median'));
    };

    $capitalMedian = static function (array $row) use ($money): string {
        if (! data_get($row, 'capital.visible', false)) {
            return 'Belum cukup / dibatasi';
        }

        return $money(data_get($row, 'capital.median'));
    };

    $densityBadge = static function (string $density): string {
        return match ($density) {
            'Tinggi' => 'text-bg-danger',
            'Rendah' => 'text-bg-success',
            'Belum ada' => 'text-bg-secondary',
            default => 'text-bg-warning',
        };
    };

    $insightClass = static function (string $level): string {
        return match ($level) {
            'opportunity' => 'border-success',
            'warning' => 'border-warning',
            'attention' => 'border-danger',
            default => 'border-info',
        };
    };

    $barPct = static function (int $value, int $max): string {
        if ($max <= 0) {
            return '0';
        }

        return number_format(min(100, ($value / $max) * 100), 2, '.', '');
    };

    $matrixClass = static function (string $level): string {
        return match ($level) {
            'Tinggi' => 'bg-primary-subtle',
            'Sedang' => 'bg-info-subtle',
            'Rendah' => 'bg-secondary-subtle',
            default => 'bg-body-tertiary text-body-secondary',
        };
    };

    $maxTypeCount = max(1, (int)($typeRanking->max('business_count') ?? 1));
    $maxDistrictCount = max(1, (int)($districtRanking->max('business_count') ?? 1));
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border shadow-sm">
        <div class="card-body p-4 d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis">Informasi Pendukung Keputusan</span>
                    <span class="badge rounded-pill text-bg-light border">Tampilan: {{ $mode['label'] ?? 'Seluruh Kota' }}</span>
                </div>
                <h1 class="h3 mb-2">{{ $mode['title'] ?? 'Perbandingan & Potensi UMKM' }}</h1>
                <p class="text-body-secondary mb-0">
                    {{ $mode['description'] ?? 'Ringkasan berdasarkan data yang tersedia pada pilihan saat ini.' }}
                    Grafik hanya digunakan ketika membantu perbandingan. Angka dan tabel tetap ditampilkan agar informasi mudah diperiksa.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 align-self-xl-start">
                <a href="{{ route('admin-dinas.analytics.index', $baseFilter) }}" class="btn btn-outline-primary">Ringkasan Data</a>
                <a href="{{ route('admin-dinas.analytics.spatial', $baseFilter) }}" class="btn btn-success">Peta Wilayah</a>
                <a href="{{ route('admin-dinas.umkm.index', $baseFilter) }}" class="btn btn-outline-secondary">Data UMKM</a>
                @if($data['can_view_financial'] ?? false)
                    <a href="{{ route('admin-dinas.analytics.financial', $baseFilter) }}" class="btn btn-warning">Ekonomi & Keuangan</a>
                @endif
            </div>
        </div>
    </section>

    <section class="alert alert-primary mb-0">
        <strong>Cara membaca:</strong>
        informasi menggunakan data UMKM yang tersedia saat ini. Perbandingan jumlah usaha dan kondisi wilayah digunakan sebagai bahan pertimbangan, bukan jaminan keberhasilan atau rekomendasi otomatis.
    </section>

    @if($data['quality_warning'] ?? false)
        <section class="alert alert-warning mb-0">
            <strong>Perhatian kualitas data.</strong>
            Terdapat catatan kualitas pada sebagian data yang digunakan. Nilai yang tercatat tetap dipertahankan apa adanya dan catatan kualitas ditampilkan terpisah.
        </section>
    @endif

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Pilih Data yang Ingin Dilihat</h2>
                    <p class="text-body-secondary mb-0">
                        Jika memilih <strong>Semua</strong>, sistem menampilkan ringkasan tingkat kota. Gunakan pilihan di bawah untuk melihat wilayah atau jenis usaha tertentu.
                    </p>
                </div>
                <a href="{{ route('admin-dinas.analytics.decision') }}" class="btn btn-sm btn-outline-secondary align-self-lg-start">Hapus Pilihan</a>
            </div>

            <form method="GET" action="{{ route('admin-dinas.analytics.decision') }}" class="row g-3">
                @foreach([
                    ['district_id', 'Kecamatan', $options['districts'] ?? []],
                    ['village_id', 'Kelurahan', $options['villages'] ?? []],
                    ['category_id', 'Kategori', $options['categories'] ?? []],
                    ['type_id', 'Jenis Usaha', $options['types'] ?? []],
                    ['marketing_method_id', 'Pemasaran', $options['marketingMethods'] ?? []],
                ] as $filter)
                    <div class="col-md-6 col-xl">
                        <label class="form-label" for="{{ $filter[0] }}">{{ $filter[1] }}</label>
                        <select class="form-select" id="{{ $filter[0] }}" name="{{ $filter[0] }}">
                            <option value="">Semua</option>
                            @foreach($filter[2] as $item)
                                <option
                                    value="{{ $item->id }}"
                                    data-region-code="{{ $item->code ?? '' }}"
                                    data-parent-code="{{ $item->parent_code ?? '' }}"
                                    @selected((string)($filters[$filter[0]] ?? '') === (string)$item->id)
                                >{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="col-md-6 col-xl">
                    <label class="form-label" for="quality_status">Kualitas Data</label>
                    <select class="form-select" id="quality_status" name="quality_status">
                        <option value="">Semua</option>
                        @foreach(($options['qualityStatuses'] ?? []) as $value)
                            <option value="{{ $value }}" @selected((string)($filters['quality_status'] ?? '') === (string)$value)>
                                {{ \Illuminate\Support\Str::headline((string)$value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary px-4" type="submit">Tampilkan Informasi</button>
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
            ['UMKM yang ditampilkan', $summary['total_umkm'] ?? 0, 'Data UMKM sesuai pilihan'],
            ['Jenis usaha tercatat', $summary['type_count'] ?? 0, 'Jenis usaha utama yang tercatat'],
            ['Kecamatan yang tercatat', $summary['district_count'] ?? 0, 'Wilayah administrasi yang tercatat'],
            ['Tenaga kerja terdata', $summary['workforce_recorded'] ?? 0, 'Jumlah tenaga kerja yang tercatat'],
            ['UMKM dengan catatan kualitas', $summary['quality_affected'] ?? 0, 'Catatan terbuka; data tidak diubah otomatis'],
        ] as $metric)
            <div class="col-md-6 col-xl">
                <div class="card border shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-body-secondary">{{ $metric[0] }}</div>
                        <div class="h2 mb-1">{{ number_format((int)$metric[1], 0, ',', '.') }}</div>
                        <div class="small text-body-secondary">{{ $metric[2] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="mb-3">
                <span class="badge text-bg-dark mb-2">Hal yang Perlu Diperhatikan</span>
                <h2 class="h5 mb-1">Informasi yang Perlu Diperhatikan</h2>
                <p class="text-body-secondary mb-0">Informasi berikut dapat digunakan sebagai bahan pertimbangan. Sistem tidak menentukan tindakan yang harus dilakukan.</p>
            </div>

            <div class="row g-3">
                @forelse($insights as $insight)
                    <div class="col-lg-6">
                        <div class="card h-100 border-2 {{ $insightClass((string)($insight['level'] ?? 'info')) }}">
                            <div class="card-body">
                                <h3 class="h6">{{ $insight['title'] }}</h3>
                                <p class="mb-2"><strong>Temuan:</strong> {{ $insight['finding'] }}</p>
                                <p class="text-body-secondary mb-0"><strong>Pertimbangan:</strong> {{ $insight['consideration'] }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><div class="alert alert-light border mb-0">Belum ada informasi tambahan yang dapat ditampilkan untuk pilihan saat ini.</div></div>
                @endforelse
            </div>
        </div>
    </section>

    @if($modeKey === 'citywide')
        <section class="row g-4">
            <div class="col-xl-6">
                <div class="card border shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <span class="badge text-bg-primary-subtle text-primary-emphasis mb-2">Struktur Usaha Kota</span>
                            <h2 class="h5 mb-1">Jenis Usaha dengan Jumlah Terbesar</h2>
                            <p class="text-body-secondary mb-0">Batang perbandingan membantu melihat perbedaan jumlah. Angka tetap ditampilkan di setiap baris.</p>
                        </div>

                        <div class="d-flex flex-column gap-3">
                            @forelse($typeRanking->take(12) as $row)
                                <a class="text-decoration-none text-body" href="{{ $row['decision_url'] }}">
                                    <div class="d-flex justify-content-between gap-3 mb-1">
                                        <span class="text-truncate">{{ $row['type_name'] }}</span>
                                        <strong>{{ number_format((int)$row['business_count'], 0, ',', '.') }}</strong>
                                    </div>
                                    <div class="progress" style="height:8px;">
                                        <div class="progress-bar" style="width: {{ $barPct((int)$row['business_count'], $maxTypeCount) }}%"></div>
                                    </div>
                                    <div class="small text-body-secondary mt-1">{{ (int)$row['district_coverage'] }} kecamatan tercatat</div>
                                </a>
                            @empty
                                <div class="alert alert-light border mb-0">Belum tersedia klasifikasi jenis usaha pada konteks aktif.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card border shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <span class="badge text-bg-info-subtle text-info-emphasis mb-2">Perbandingan Wilayah</span>
                            <h2 class="h5 mb-1">Distribusi UMKM Antar-Kecamatan</h2>
                            <p class="text-body-secondary mb-0">Jumlah UMKM dibaca bersama keragaman jenis usaha dan bagian tiga jenis usaha terbesar.</p>
                        </div>

                        <div class="d-flex flex-column gap-3">
                            @forelse($districtRanking as $row)
                                <a class="text-decoration-none text-body" href="{{ $row['decision_url'] }}">
                                    <div class="d-flex justify-content-between gap-3 mb-1">
                                        <span>{{ $row['name'] }}</span>
                                        <strong>{{ number_format((int)$row['business_count'], 0, ',', '.') }}</strong>
                                    </div>
                                    <div class="progress" style="height:8px;">
                                        <div class="progress-bar bg-success" style="width: {{ $barPct((int)$row['business_count'], $maxDistrictCount) }}%"></div>
                                    </div>
                                    <div class="small text-body-secondary mt-1">
                                        {{ (int)$row['type_count'] }} jenis usaha · Bagian 3 jenis terbesar {{ number_format((float)$row['top3_type_share_percent'], 2, ',', '.') }}%
                                    </div>
                                </a>
                            @empty
                                <div class="alert alert-light border mb-0">Belum tersedia asosiasi kecamatan pada konteks aktif.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                    <div>
                        <span class="badge text-bg-secondary mb-2">Perbandingan Jenis Usaha per Kecamatan</span>
                        <h2 class="h5 mb-1">Jenis Usaha × Kecamatan</h2>
                        <p class="text-body-secondary mb-0">
                            Menampilkan maksimal {{ (int)($matrix['type_limit'] ?? 12) }} jenis usaha dengan jumlah terbesar agar matriks tetap terbaca.
                            Warna hanya menunjukkan perbedaan jumlah dalam baris jenis usaha yang sama.
                        </p>
                    </div>
                    <div class="small text-body-secondary align-self-lg-start">Klik angka untuk rincian kombinasi wilayah × jenis.</div>
                </div>

                @if($matrixRows->isEmpty())
                    <div class="alert alert-light border mb-0">Belum tersedia data untuk matriks distribusi.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-start" style="min-width:220px;">Jenis Usaha</th>
                                    @foreach($matrixDistricts as $district)
                                        <th style="min-width:110px;">{{ $district['name'] }}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matrixRows as $row)
                                    <tr>
                                        <th class="text-start fw-semibold">{{ $row['type_name'] }}</th>
                                        @foreach(($row['cells'] ?? []) as $cell)
                                            <td class="{{ $matrixClass((string)$cell['level']) }}">
                                                <a href="{{ $cell['decision_url'] }}" class="text-decoration-none fw-semibold text-body" title="{{ $cell['district_name'] }} · {{ $cell['level'] }}">
                                                    {{ number_format((int)$cell['count'], 0, ',', '.') }}
                                                </a>
                                            </td>
                                        @endforeach
                                        <td class="fw-bold">{{ number_format((int)$row['total'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="small text-body-secondary mt-2">Kosong/0 berarti belum ada usaha sejenis tercatat pada wilayah yang tercatat tersebut; kondisi itu tidak otomatis disebut peluang.</div>
                @endif
            </div>
        </section>

        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="badge text-bg-success-subtle text-success-emphasis mb-2">Kondisi yang Perlu Ditinjau</span>
                    <h2 class="h5 mb-1">Pasangan Jenis Usaha–Kecamatan yang Perlu Ditinjau</h2>
                    <p class="text-body-secondary mb-0">
                        Daftar ini menampilkan jenis usaha yang jumlahnya relatif sedikit dan memiliki data ekonomi yang cukup untuk dibandingkan dengan jenis usaha yang sama di kota. Informasi ini bukan jaminan peluang usaha.
                    </p>
                </div>

                @if(!($data['can_view_financial'] ?? false))
                    <div class="alert alert-info">
                        Data ekonomi tidak tersedia untuk akun ini. Sistem hanya menampilkan perbandingan jumlah usaha antarwilayah.
                    </div>
                @else
                    <div class="d-flex flex-wrap gap-2 mb-3 small">
                        <span class="badge text-bg-light border">Data ekonomi: {{ $citywide['economic_metric_label'] ?? 'belum cukup' }}</span>
                        <span class="badge text-bg-light border">Berdasarkan {{ number_format((int)($citywide['economic_sample_count'] ?? 0), 0, ',', '.') }} data usaha</span>
                        <span class="badge text-bg-light border">Data tersedia {{ number_format((float)($citywide['economic_coverage_percent'] ?? 0), 2, ',', '.') }}%</span>
                    </div>
                @endif

                @if(($data['can_view_financial'] ?? false) && $potentialPairs->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Jenis Usaha</th>
                                    <th>Kecamatan</th>
                                    <th class="text-end">Jumlah</th>
                                    <th>Nilai Tengah Data Ekonomi</th>
                                    <th>Nilai Tengah Pembanding</th>
                                    <th>Nilai Tengah Modal</th>
                                    <th>Kualitas Data</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($potentialPairs as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['type_name'] }}</td>
                                        <td>{{ $row['district_name'] }}</td>
                                        <td class="text-end">{{ number_format((int)$row['business_count'], 0, ',', '.') }}</td>
                                        <td>
                                            <div class="small text-body-secondary">{{ $row['economic_metric_label'] }}</div>
                                            <strong>{{ $economicMedian($row) }}</strong>
                                        </td>
                                        <td>{{ $money($row['reference_median'] ?? null) }}</td>
                                        <td>{{ $capitalMedian($row) }}</td>
                                        <td>
                                            @if($row['quality_warning'])
                                                <span class="badge text-bg-warning">Ada catatan</span>
                                            @else
                                                <span class="badge text-bg-success">Tidak ada catatan</span>
                                            @endif
                                        </td>
                                        <td><a href="{{ $row['decision_url'] }}" class="btn btn-sm btn-outline-primary">Lihat</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($structuralGaps->isNotEmpty())
                    <div class="alert alert-light border mb-3">
                        Belum ada pasangan yang memenuhi seluruh syarat perbandingan pada konteks aktif. Tabel berikut hanya menunjukkan <strong>jumlah usaha relatif sedikit</strong>; bukan klaim peluang ekonomi.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead><tr><th>Jenis Usaha</th><th>Kecamatan</th><th class="text-end">Jumlah</th><th>Keterangan</th><th></th></tr></thead>
                            <tbody>
                                @foreach($structuralGaps as $row)
                                    <tr>
                                        <td>{{ $row['type_name'] }}</td>
                                        <td>{{ $row['district_name'] }}</td>
                                        <td class="text-end">{{ number_format((int)$row['business_count'], 0, ',', '.') }}</td>
                                        <td>Konsentrasi relatif rendah; potensi ekonomi belum dapat disimpulkan dari jumlah saja.</td>
                                        <td><a href="{{ $row['decision_url'] }}" class="btn btn-sm btn-outline-secondary">Tinjau</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-light border mb-0">Belum ada pasangan jenis usaha–wilayah yang memenuhi kriteria minimum pembandingan pada konteks aktif.</div>
                @endif
            </div>
        </section>

        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <details>
                    <summary class="fw-semibold">Lihat tabel lengkap seluruh jenis usaha</summary>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Jenis Usaha</th>
                                    <th class="text-end">UMKM</th>
                                    <th class="text-end">Kecamatan</th>
                                    <th class="text-end">Tenaga Kerja</th>
                                    <th>Data Ekonomi</th>
                                    <th>Nilai Tengah Modal</th>
                                    <th>Kualitas Data</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($typeRanking as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['type_name'] }}</td>
                                        <td class="text-end">{{ number_format((int)$row['business_count'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format((int)$row['district_coverage'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format((int)$row['employees_total'], 0, ',', '.') }}</td>
                                        <td>{{ $economicMedian($row) }}</td>
                                        <td>{{ $capitalMedian($row) }}</td>
                                        <td>{{ $row['quality_warning'] ? 'Ada catatan' : 'Tidak ada catatan' }}</td>
                                        <td><a href="{{ $row['decision_url'] }}" class="btn btn-sm btn-outline-primary">Lihat</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </section>
    @endif

    @if($selectedType)
        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-3">
                    <div>
                        <span class="badge text-bg-info-subtle text-info-emphasis mb-2">Perbandingan Usaha Sejenis</span>
                        <h2 class="h5 mb-1">Perbandingan {{ $selectedType['name'] }} Antar-Kecamatan</h2>
                        <p class="text-body-secondary mb-0">Jumlah dan persentase membantu membandingkan usaha sejenis antarwilayah. Data ekonomi hanya ditampilkan jika tersedia dan akun memiliki kewenangan.</p>
                    </div>
                    <span class="badge text-bg-light border align-self-xl-start">{{ $selectedType['name'] }}</span>
                </div>

                @if($competition->isEmpty())
                    <div class="alert alert-light border mb-0">Belum tersedia data pembanding untuk jenis usaha terpilih.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Kecamatan</th>
                                    <th class="text-end">Seluruh UMKM</th>
                                    <th class="text-end">Usaha Sejenis</th>
                                    <th class="text-end">Persentase</th>
                                    <th>Jumlah di Wilayah</th>
                                    <th class="text-end">Tenaga Kerja</th>
                                    <th>Data Ekonomi</th>
                                    <th>Nilai Tengah Modal</th>
                                    <th>Kualitas Data</th>
                                    <th>Interpretasi</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($competition as $row)
                                    <tr>
                                        <td><div class="fw-semibold">{{ $row['name'] }}</div><div class="small text-body-secondary">{{ $row['code'] }}</div></td>
                                        <td class="text-end">{{ number_format((int)$row['district_total_umkm'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format((int)$row['business_count'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format((float)$row['share_percent'], 2, ',', '.') }}%</td>
                                        <td><span class="badge {{ $densityBadge((string)$row['density_level']) }}">{{ $row['density_level'] }}</span></td>
                                        <td class="text-end">{{ number_format((int)$row['employees_total'], 0, ',', '.') }}</td>
                                        <td>
                                            @if($row['economic_metric_label'])
                                                <div class="small text-body-secondary">{{ $row['economic_metric_label'] }}</div>
                                                <div class="fw-semibold">{{ $economicMedian($row) }}</div>
                                                <div class="small text-body-secondary">Berdasarkan data {{ (int)data_get($row, 'economic.sample_count', 0) }} usaha</div>
                                            @else
                                                <span class="text-body-secondary">Tidak tersedia</span>
                                            @endif
                                        </td>
                                        <td>{{ $capitalMedian($row) }}</td>
                                        <td>
                                            @if($row['quality_warning'])<span class="badge text-bg-warning">Ada catatan</span>@else<span class="badge text-bg-success">Tidak ada catatan</span>@endif
                                        </td>
                                        <td style="min-width:220px;">
                                            {{ $row['context_label'] }}
                                            @if($row['potential_relative'])<div class="mt-1"><span class="badge text-bg-success">Kondisi yang perlu ditinjau</span></div>@endif
                                        </td>
                                        <td><div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ $row['drill_down_url'] }}">Data</a><a class="btn btn-sm btn-outline-success" href="{{ $row['map_url'] }}">Peta</a></div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="small text-body-secondary">
                        Batas jumlah kelompok bawah: {{ data_get($competitionSummary, 'business_count_q1') !== null ? number_format((float)data_get($competitionSummary, 'business_count_q1'), 1, ',', '.') : '-' }}
                        · Batas jumlah kelompok atas: {{ data_get($competitionSummary, 'business_count_q3') !== null ? number_format((float)data_get($competitionSummary, 'business_count_q3'), 1, ',', '.') : '-' }}
                        · Data ekonomi pembanding: {{ data_get($competitionSummary, 'economic_metric_label') ?: 'belum cukup / tidak tersedia' }}.
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if($selectedDistrict)
        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="badge text-bg-success-subtle text-success-emphasis mb-2">Kondisi Wilayah yang Perlu Ditinjau</span>
                    <h2 class="h5 mb-1">Komposisi Jenis Usaha pada {{ $selectedVillage['name'] ?? $selectedDistrict['name'] }}</h2>
                    <p class="text-body-secondary mb-0">Jumlah setiap jenis usaha dibandingkan dalam wilayah yang dipilih. Data ekonomi digunakan sebagai informasi tambahan jika tersedia.</p>
                </div>

                @if($opportunities->isEmpty())
                    <div class="alert alert-light border mb-0">Belum ada data yang cukup pada wilayah aktif.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Jenis Usaha</th>
                                    <th class="text-end">Jumlah</th>
                                    <th>Posisi Jumlah</th>
                                    <th>Data Ekonomi</th>
                                    <th>Nilai Tengah Modal</th>
                                    <th>Kualitas Data</th>
                                    <th>Interpretasi</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($opportunities as $row)
                                    <tr @class(['table-success' => $row['potential_relative']])>
                                        <td class="fw-semibold">{{ $row['type_name'] }}</td>
                                        <td class="text-end">{{ number_format((int)$row['business_count'], 0, ',', '.') }}</td>
                                        <td>@if($row['low_count_group'])<span class="badge text-bg-success">Jumlah relatif sedikit</span>@else<span class="badge text-bg-light border">Pembanding</span>@endif</td>
                                        <td>
                                            @if($row['economic_metric_label'])
                                                <div class="small text-body-secondary">{{ $row['economic_metric_label'] }}</div>
                                                <div class="fw-semibold">{{ $economicMedian($row) }}</div>
                                                <div class="small text-body-secondary">Berdasarkan data {{ (int)data_get($row, 'economic.sample_count', 0) }} usaha</div>
                                            @else
                                                <span class="text-body-secondary">Belum cukup / tidak tersedia</span>
                                            @endif
                                        </td>
                                        <td>{{ $capitalMedian($row) }}</td>
                                        <td>@if($row['quality_warning'])<span class="badge text-bg-warning">Ada catatan</span>@else<span class="badge text-bg-success">Tidak ada catatan</span>@endif</td>
                                        <td>{{ $row['context_label'] }} @if($row['potential_relative'])<div class="mt-1"><span class="badge text-bg-success">Kondisi yang perlu ditinjau</span></div>@endif</td>
                                        <td><div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ $row['drill_down_url'] }}">Data</a><a class="btn btn-sm btn-outline-success" href="{{ $row['map_url'] }}">Peta</a></div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="small text-body-secondary">Jumlah usaha yang relatif sedikit tidak otomatis berarti peluang usaha. Data ekonomi dan jumlah data yang cukup tetap perlu diperhatikan.</div>
                @endif
            </div>
        </section>
    @endif

    @if($selectedType)
        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="badge text-bg-secondary mb-2">Informasi Lokasi Pendukung</span>
                    <h2 class="h5 mb-1">Kedekatan Usaha Sejenis · Opsional</h2>
                    <p class="text-body-secondary mb-0">Informasi jarak hanya digunakan sebagai pelengkap dan tidak menentukan hasil perbandingan. Jarak dihitung dari titik lokasi yang tersedia.</p>
                </div>

                <details class="border rounded-3 bg-body-tertiary">
                    <summary class="p-3 fw-semibold" style="cursor:pointer;">Tampilkan informasi jarak antarusaha</summary>
                    <div class="p-3 border-top bg-body">
                        @if(!($data['can_view_coordinates'] ?? false))
                            <div class="alert alert-warning mb-0">Informasi jarak antarusaha hanya tersedia bagi pengguna yang berwenang melihat lokasi.</div>
                        @elseif(!($microSpatial['available'] ?? false))
                            <div class="alert alert-light border mb-0">Informasi lokasi belum tersedia untuk pilihan saat ini.</div>
                        @elseif($microRows->isEmpty())
                            <div class="alert alert-light border mb-0">Tidak ada titik titik lokasi untuk jenis usaha dan konteks wilayah terpilih.</div>
                        @else
                            <div class="d-flex flex-wrap gap-2 mb-3 small">
                                <span class="badge text-bg-light border">Titik pembanding di kota: {{ number_format((int)($microSpatial['pool_count'] ?? 0), 0, ',', '.') }} titik</span>
                                <span class="badge text-bg-light border">Ditampilkan: {{ number_format((int)($microSpatial['focus_count'] ?? 0), 0, ',', '.') }} titik</span>
                                <span class="badge text-bg-light border">Pembanding dari seluruh wilayah kota</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>UMKM</th><th>Wilayah</th><th class="text-end">Terdekat</th><th class="text-end">≤250 m</th><th class="text-end">≤500 m</th><th class="text-end">≤1 km</th><th>Kualitas Data</th><th></th></tr></thead>
                                    <tbody>
                                        @foreach($microRows as $row)
                                            <tr>
                                                <td><div class="fw-semibold">{{ $row['business_name'] }}</div><div class="small text-body-secondary">{{ $row['umkm_code'] }}</div></td>
                                                <td><div>{{ $row['district_name'] }}</div><div class="small text-body-secondary">{{ $row['village_name'] ?: 'Kelurahan belum tersedia' }}</div></td>
                                                <td class="text-end">{{ $row['nearest_same_type_meters'] !== null ? number_format((float)$row['nearest_same_type_meters'], 1, ',', '.') . ' m' : '—' }}</td>
                                                <td class="text-end">{{ number_format((int)$row['neighbors_250m'], 0, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format((int)$row['neighbors_500m'], 0, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format((int)$row['neighbors_1000m'], 0, ',', '.') }}</td>
                                                <td>@if($row['quality_warning'])<span class="badge text-bg-warning">Ada catatan</span>@else<span class="badge text-bg-success">Tidak ada catatan</span>@endif</td>
                                                <td><a class="btn btn-sm btn-outline-primary" href="{{ $row['detail_url'] }}">Detail</a></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </details>
            </div>
        </section>
    @endif

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5 mb-2">Cara Membaca Informasi</h2>
            <div class="row g-3 small">
                <div class="col-lg-6">
                    <ul class="mb-0">
                        <li>Informasi menggunakan data UMKM yang tersedia saat ini dan membandingkan kondisi antarwilayah.</li>
                        <li>Mode Semua menghasilkan analitik tingkat kota; filter berfungsi sebagai rincian.</li>
                        <li>Perbandingan menggunakan jenis usaha utama yang tercatat.</li>
                        <li>Data yang tercatat tidak diubah secara otomatis. Catatan kualitas ditampilkan terpisah.</li>
                        <li>Data ekonomi kelompok hanya ditampilkan jika sedikitnya {{ $minimumGroupSize }} usaha memiliki data yang tersedia.</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="mb-0">
                        <li>Jenis data ekonomi yang digunakan dipilih dari data yang paling banyak tersedia dan memenuhi jumlah minimum.</li>
                        <li>Sistem tidak memprediksi kondisi masa depan dan tidak menyimpulkan hubungan sebab-akibat.</li>
                        <li>Sistem tidak menentukan usaha yang harus dibuka atau ditutup.</li>
                        <li>Informasi jarak antarusaha hanya sebagai pelengkap dan tidak menentukan hasil perbandingan.</li>
                        <li>Tombol rincian membuka data sesuai kewenangan pengguna tanpa mengubah data.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="{{ asset('assets/js/pages/admin-dinas/admin-dinas-region-cascade.js') }}" defer></script>
@endsection
