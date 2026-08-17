@extends('layouts.dashboard')

@section('title', 'Analitik Keputusan Admin Dinas')

@section('content')
@php
    $filters = $data['filters'] ?? [];
    $options = $data['filter_options'] ?? [];
    $summary = $data['summary'] ?? [];
    $mode = $data['analysis_mode'] ?? [
        'key' => 'citywide',
        'label' => 'Seluruh Kota',
        'title' => 'Gambaran Keputusan Seluruh Kota',
        'description' => 'Analitik baseline pada konteks aktif.',
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
                    <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis">Decision Support · Tahun Pertama</span>
                    <span class="badge rounded-pill text-bg-light border">Mode: {{ $mode['label'] ?? 'Seluruh Kota' }}</span>
                </div>
                <h1 class="h3 mb-2">{{ $mode['title'] ?? 'Analitik Keputusan Admin Dinas' }}</h1>
                <p class="text-body-secondary mb-0">
                    {{ $mode['description'] ?? 'Analitik baseline pada konteks aktif.' }}
                    Bentuk visual hanya digunakan ketika membantu perbandingan; angka, tabel, dan interpretasi tetap menjadi bukti utama.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 align-self-xl-start">
                <a href="{{ route('admin-dinas.analytics.index', $baseFilter) }}" class="btn btn-outline-primary">Analitik Umum</a>
                <a href="{{ route('admin-dinas.analytics.spatial', $baseFilter) }}" class="btn btn-success">Peta Wilayah</a>
                <a href="{{ route('admin-dinas.umkm.index', $baseFilter) }}" class="btn btn-outline-secondary">Data UMKM</a>
                @if($data['can_view_financial'] ?? false)
                    <a href="{{ route('admin-dinas.analytics.financial', $baseFilter) }}" class="btn btn-warning">Ekonomi & Keuangan</a>
                @endif
            </div>
        </div>
    </section>

    <section class="alert alert-primary mb-0">
        <strong>Batas interpretasi:</strong>
        seluruh hasil merupakan pembacaan baseline cross-sectional Tahun Pertama.
        Konsentrasi, kesenjangan distribusi, dan potensi bersifat relatif; bukan prediksi keberhasilan,
        rekomendasi otomatis, atau bukti hubungan sebab-akibat.
    </section>

    @if($data['quality_warning'] ?? false)
        <section class="alert alert-warning mb-0">
            <strong>Perhatian mutu data.</strong>
            Terdapat flag mutu pada sebagian record yang digunakan. Nilai sumber tetap dipertahankan apa adanya dan tidak dinormalisasi,
            dibuang, atau dibenarkan secara otomatis.
        </section>
    @endif

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Konteks Analisis</h2>
                    <p class="text-body-secondary mb-0">
                        Opsi <strong>Semua</strong> tetap menghasilkan analitik keputusan tingkat kota. Filter digunakan untuk mempersempit analisis, bukan untuk mengaktifkannya.
                    </p>
                </div>
                <a href="{{ route('admin-dinas.analytics.decision') }}" class="btn btn-sm btn-outline-secondary align-self-lg-start">Reset</a>
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
                    <label class="form-label" for="quality_status">Mutu Data</label>
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
                    <button class="btn btn-primary px-4" type="submit">Terapkan Analisis</button>
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
            ['Jenis usaha teridentifikasi', $summary['type_count'] ?? 0, 'Klasifikasi primer pada konteks'],
            ['Kecamatan tercakup', $summary['district_count'] ?? 0, 'Asosiasi administratif yang terdata'],
            ['Tenaga kerja terdata', $summary['workforce_recorded'] ?? 0, 'Akumulasi employee_count sumber'],
            ['UMKM dengan flag mutu', $summary['quality_affected'] ?? 0, 'Flag terbuka; bukan koreksi otomatis'],
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
                <span class="badge text-bg-dark mb-2">Ringkasan Keputusan</span>
                <h2 class="h5 mb-1">Temuan yang Perlu Dibaca dalam Konteks</h2>
                <p class="text-body-secondary mb-0">Temuan dipisahkan dari pertimbangan. Sistem tidak mengubahnya menjadi instruksi otomatis.</p>
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
                    <div class="col-12"><div class="alert alert-light border mb-0">Belum ada temuan yang dapat diturunkan dari konteks aktif.</div></div>
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
                            <p class="text-body-secondary mb-0">Visual bar hanya membantu membandingkan besaran; angka tetap ditampilkan secara eksplisit.</p>
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
                                    <div class="small text-body-secondary mt-1">{{ (int)$row['district_coverage'] }} kecamatan terasosiasi</div>
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
                            <p class="text-body-secondary mb-0">Jumlah UMKM dibaca bersama keragaman jenis usaha dan proporsi tiga jenis terbesar.</p>
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
                                        {{ (int)$row['type_count'] }} jenis usaha · Top-3 share {{ number_format((float)$row['top3_type_share_percent'], 2, ',', '.') }}%
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
                        <span class="badge text-bg-secondary mb-2">Matriks Distribusi</span>
                        <h2 class="h5 mb-1">Jenis Usaha × Kecamatan</h2>
                        <p class="text-body-secondary mb-0">
                            Menampilkan maksimal {{ (int)($matrix['type_limit'] ?? 12) }} jenis usaha dengan jumlah terbesar agar matriks tetap terbaca.
                            Warna hanya menunjukkan intensitas relatif dalam baris jenis usaha yang sama.
                        </p>
                    </div>
                    <div class="small text-body-secondary align-self-lg-start">Klik angka untuk drill-down kombinasi wilayah × jenis.</div>
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
                    <div class="small text-body-secondary mt-2">Kosong/0 berarti belum ada usaha sejenis tercatat pada asosiasi wilayah tersebut; kondisi itu tidak otomatis disebut peluang.</div>
                @endif
            </div>
        </section>

        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="badge text-bg-success-subtle text-success-emphasis mb-2">Potensi Relatif Lintas Wilayah</span>
                    <h2 class="h5 mb-1">Pasangan Jenis Usaha–Kecamatan yang Perlu Ditinjau</h2>
                    <p class="text-body-secondary mb-0">
                        Konsentrasi rendah hanya menjadi <em>indikasi potensi relatif</em> jika median indikator ekonomi kelompok tidak lebih rendah dari median jenis usaha yang sama pada pembanding kota dan jumlah sampel memenuhi batas minimum.
                    </p>
                </div>

                @if(!($data['can_view_financial'] ?? false))
                    <div class="alert alert-info">
                        Kewenangan finansial tidak aktif. Sistem hanya dapat menunjukkan kesenjangan distribusi dan tidak menyatakan potensi ekonomi.
                    </div>
                @else
                    <div class="d-flex flex-wrap gap-2 mb-3 small">
                        <span class="badge text-bg-light border">Indikator: {{ $citywide['economic_metric_label'] ?? 'belum cukup' }}</span>
                        <span class="badge text-bg-light border">n={{ number_format((int)($citywide['economic_sample_count'] ?? 0), 0, ',', '.') }}</span>
                        <span class="badge text-bg-light border">Coverage {{ number_format((float)($citywide['economic_coverage_percent'] ?? 0), 2, ',', '.') }}%</span>
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
                                    <th>Median Indikator</th>
                                    <th>Median Pembanding</th>
                                    <th>Median Modal</th>
                                    <th>Mutu</th>
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
                                                <span class="badge text-bg-warning">Ada flag</span>
                                            @else
                                                <span class="badge text-bg-success">Tanpa flag terbuka</span>
                                            @endif
                                        </td>
                                        <td><a href="{{ $row['decision_url'] }}" class="btn btn-sm btn-outline-primary">Analisis</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($structuralGaps->isNotEmpty())
                    <div class="alert alert-light border mb-3">
                        Belum ada pasangan yang memenuhi seluruh rule potensi pada konteks aktif. Tabel berikut hanya menunjukkan <strong>konsentrasi relatif rendah</strong>; bukan klaim peluang ekonomi.
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
                                    <th>Sinyal Ekonomi</th>
                                    <th>Median Modal</th>
                                    <th>Mutu</th>
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
                                        <td>{{ $row['quality_warning'] ? 'Ada flag' : 'Tanpa flag terbuka' }}</td>
                                        <td><a href="{{ $row['decision_url'] }}" class="btn btn-sm btn-outline-primary">Analisis</a></td>
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
                        <span class="badge text-bg-info-subtle text-info-emphasis mb-2">Persaingan & Konsentrasi</span>
                        <h2 class="h5 mb-1">Perbandingan {{ $selectedType['name'] }} Antar-Kecamatan</h2>
                        <p class="text-body-secondary mb-0">Jumlah dan proporsi menjadi indikator konsentrasi. Sinyal ekonomi hanya ditampilkan jika data dan kewenangan mencukupi.</p>
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
                                    <th class="text-end">Proporsi</th>
                                    <th>Konsentrasi</th>
                                    <th class="text-end">Tenaga Kerja</th>
                                    <th>Sinyal Ekonomi</th>
                                    <th>Median Modal</th>
                                    <th>Mutu</th>
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
                                                <div class="small text-body-secondary">n={{ (int)data_get($row, 'economic.sample_count', 0) }}</div>
                                            @else
                                                <span class="text-body-secondary">Tidak tersedia</span>
                                            @endif
                                        </td>
                                        <td>{{ $capitalMedian($row) }}</td>
                                        <td>
                                            @if($row['quality_warning'])<span class="badge text-bg-warning">Ada flag</span>@else<span class="badge text-bg-success">Tanpa flag terbuka</span>@endif
                                        </td>
                                        <td style="min-width:220px;">
                                            {{ $row['context_label'] }}
                                            @if($row['potential_relative'])<div class="mt-1"><span class="badge text-bg-success">Indikasi potensi relatif</span></div>@endif
                                        </td>
                                        <td><div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ $row['drill_down_url'] }}">Data</a><a class="btn btn-sm btn-outline-success" href="{{ $row['map_url'] }}">Peta</a></div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="small text-body-secondary">
                        Q1 jumlah usaha: {{ data_get($competitionSummary, 'business_count_q1') !== null ? number_format((float)data_get($competitionSummary, 'business_count_q1'), 1, ',', '.') : '-' }}
                        · Q3: {{ data_get($competitionSummary, 'business_count_q3') !== null ? number_format((float)data_get($competitionSummary, 'business_count_q3'), 1, ',', '.') : '-' }}
                        · Referensi ekonomi: {{ data_get($competitionSummary, 'economic_metric_label') ?: 'belum cukup / tidak tersedia' }}.
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if($selectedDistrict)
        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="badge text-bg-success-subtle text-success-emphasis mb-2">Potensi Relatif Wilayah</span>
                    <h2 class="h5 mb-1">Komposisi Jenis Usaha pada {{ $selectedVillage['name'] ?? $selectedDistrict['name'] }}</h2>
                    <p class="text-body-secondary mb-0">Jumlah usaha dibandingkan antarjenis dalam wilayah aktif; label potensi tetap membutuhkan sinyal ekonomi yang memadai.</p>
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
                                    <th>Sinyal Ekonomi</th>
                                    <th>Median Modal</th>
                                    <th>Mutu</th>
                                    <th>Interpretasi</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($opportunities as $row)
                                    <tr @class(['table-success' => $row['potential_relative']])>
                                        <td class="fw-semibold">{{ $row['type_name'] }}</td>
                                        <td class="text-end">{{ number_format((int)$row['business_count'], 0, ',', '.') }}</td>
                                        <td>@if($row['low_count_group'])<span class="badge text-bg-success">Kuartil bawah</span>@else<span class="badge text-bg-light border">Pembanding</span>@endif</td>
                                        <td>
                                            @if($row['economic_metric_label'])
                                                <div class="small text-body-secondary">{{ $row['economic_metric_label'] }}</div>
                                                <div class="fw-semibold">{{ $economicMedian($row) }}</div>
                                                <div class="small text-body-secondary">n={{ (int)data_get($row, 'economic.sample_count', 0) }}</div>
                                            @else
                                                <span class="text-body-secondary">Belum cukup / tidak tersedia</span>
                                            @endif
                                        </td>
                                        <td>{{ $capitalMedian($row) }}</td>
                                        <td>@if($row['quality_warning'])<span class="badge text-bg-warning">Ada flag</span>@else<span class="badge text-bg-success">Tanpa flag terbuka</span>@endif</td>
                                        <td>{{ $row['context_label'] }} @if($row['potential_relative'])<div class="mt-1"><span class="badge text-bg-success">Indikasi potensi relatif</span></div>@endif</td>
                                        <td><div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ $row['drill_down_url'] }}">Data</a><a class="btn btn-sm btn-outline-success" href="{{ $row['map_url'] }}">Peta</a></div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="small text-body-secondary">Jenis usaha pada kuartil bawah tidak otomatis dianggap potensial; sinyal ekonomi dan batas minimum data tetap harus terpenuhi.</div>
                @endif
            </div>
        </section>
    @endif

    @if($selectedType)
        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="badge text-bg-secondary mb-2">Informasi Spasial Pendukung</span>
                    <h2 class="h5 mb-1">Kedekatan Usaha Sejenis · Opsional</h2>
                    <p class="text-body-secondary mb-0">Informasi ini tidak digunakan sebagai filter atau penentu label Analitik Keputusan. Jarak hanya memberi konteks tambahan dari titik coordinate-mapped.</p>
                </div>

                <details class="border rounded-3 bg-body-tertiary">
                    <summary class="p-3 fw-semibold" style="cursor:pointer;">Tampilkan informasi kedekatan spasial</summary>
                    <div class="p-3 border-top bg-body">
                        @if(!($data['can_view_coordinates'] ?? false))
                            <div class="alert alert-warning mb-0">Informasi kedekatan spasial memerlukan izin <code>umkm.sensitive.coordinate</code>.</div>
                        @elseif(!($microSpatial['available'] ?? false))
                            <div class="alert alert-light border mb-0">Informasi spasial belum tersedia pada konteks ini.</div>
                        @elseif($microRows->isEmpty())
                            <div class="alert alert-light border mb-0">Tidak ada titik coordinate-mapped untuk jenis usaha dan konteks wilayah terpilih.</div>
                        @else
                            <div class="d-flex flex-wrap gap-2 mb-3 small">
                                <span class="badge text-bg-light border">Pool kota: {{ number_format((int)($microSpatial['pool_count'] ?? 0), 0, ',', '.') }} titik</span>
                                <span class="badge text-bg-light border">Ditampilkan: {{ number_format((int)($microSpatial['focus_count'] ?? 0), 0, ',', '.') }} titik</span>
                                <span class="badge text-bg-light border">Pembanding kedekatan lintas batas administratif</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th>UMKM</th><th>Wilayah</th><th class="text-end">Terdekat</th><th class="text-end">≤250 m</th><th class="text-end">≤500 m</th><th class="text-end">≤1 km</th><th>Mutu</th><th></th></tr></thead>
                                    <tbody>
                                        @foreach($microRows as $row)
                                            <tr>
                                                <td><div class="fw-semibold">{{ $row['business_name'] }}</div><div class="small text-body-secondary">{{ $row['umkm_code'] }}</div></td>
                                                <td><div>{{ $row['district_name'] }}</div><div class="small text-body-secondary">{{ $row['village_name'] ?: 'Kelurahan belum tersedia' }}</div></td>
                                                <td class="text-end">{{ $row['nearest_same_type_meters'] !== null ? number_format((float)$row['nearest_same_type_meters'], 1, ',', '.') . ' m' : '—' }}</td>
                                                <td class="text-end">{{ number_format((int)$row['neighbors_250m'], 0, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format((int)$row['neighbors_500m'], 0, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format((int)$row['neighbors_1000m'], 0, ',', '.') }}</td>
                                                <td>@if($row['quality_warning'])<span class="badge text-bg-warning">Ada flag</span>@else<span class="badge text-bg-success">Tanpa flag terbuka</span>@endif</td>
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
            <h2 class="h5 mb-2">Metodologi & Batas Penggunaan</h2>
            <div class="row g-3 small">
                <div class="col-lg-6">
                    <ul class="mb-0">
                        <li>Scope: baseline cross-sectional dan spasial Tahun Pertama.</li>
                        <li>Mode Semua menghasilkan analitik tingkat kota; filter berfungsi sebagai drill-down.</li>
                        <li>Klasifikasi pembanding menggunakan jenis usaha primer yang tersimpan.</li>
                        <li>Nilai sumber dipertahankan; anomali tidak dikeluarkan secara otomatis.</li>
                        <li>Agregat ekonomi minimum {{ $minimumGroupSize }} record terisi.</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="mb-0">
                        <li>Indikator ekonomi dipilih berdasarkan coverage numerik terbesar yang memenuhi batas minimum; jika seri, urutan metrik yang dikonfigurasi dipertahankan.</li>
                        <li>Tidak melakukan forecasting atau causal inference.</li>
                        <li>Tidak menghasilkan rekomendasi otomatis "harus membuka/menutup usaha".</li>
                        <li>Informasi spasial hanya pendukung dan tidak menentukan label keputusan.</li>
                        <li>Drill-down tetap mengarah ke data read-only sesuai kewenangan.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="{{ asset('assets/js/pages/admin-dinas/admin-dinas-region-cascade.js') }}" defer></script>
@endsection
