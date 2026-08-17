@extends('layouts.dashboard')

@section('title', 'Analitik Keputusan Baseline')

@section('content')
@php
    $selectedUmkm = $data['selected_umkm'] ?? null;
    $selectedType = $data['selected_type'] ?? null;
    $selectedDistrict = $data['selected_district'] ?? null;
    $position = $data['position'] ?? null;
    $competition = collect($data['competition_by_district'] ?? []);
    $competitionSummary = $data['competition_summary'] ?? [];
    $opportunities = collect($data['opportunity_types'] ?? []);
    $opportunitySummary = $data['opportunity_summary'] ?? [];
    $minimumGroupSize = (int) data_get($data, 'methodology.minimum_group_size', 3);
    $maxCompetitionCount = max(1, (int) ($competition->max('business_count') ?? 1));
    $maxOpportunityCount = max(1, (int) ($opportunities->max('business_count') ?? 1));

    $barPercent = static function (int $value, int $max): string {
        if ($max <= 0) {
            return '0';
        }

        return number_format(min(100, ($value / $max) * 100), 2, '.', '');
    };

    $money = static function ($value): string {
        if ($value === null || $value === '') {
            return 'Belum tersedia';
        }

        return 'Rp' . number_format((float) $value, 0, ',', '.');
    };

    $number = static function ($value): string {
        if ($value === null || $value === '') {
            return 'Belum tersedia';
        }

        return number_format((float) $value, 0, ',', '.');
    };

    $aggregateValue = static function (?array $metric, callable $formatter, string $stat = 'median'): string {
        if (! $metric) {
            return 'Belum tersedia';
        }

        if (! ($metric['visible'] ?? false)) {
            return 'Dibatasi';
        }

        return $formatter($metric[$stat] ?? null);
    };

    $densityBadge = static function (string $density): string {
        return match ($density) {
            'Tinggi' => 'text-bg-danger',
            'Rendah' => 'text-bg-success',
            'Belum ada' => 'text-bg-secondary',
            default => 'text-bg-warning',
        };
    };
@endphp

<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Tahun Pertama · baseline cross-sectional dan spasial</p>
            <h1 class="h3 mb-2">Analitik Keputusan Pelaku UMKM</h1>
            <p class="mb-0">
                Membantu membaca posisi usaha, kepadatan usaha sejenis antarwilayah, dan indikasi potensi jenis usaha tanpa forecasting atau rekomendasi otomatis.
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('pelaku-umkm.dashboard') }}">Kembali ke Dashboard</a>
    </div>

    <div class="alert alert-primary">
        <strong>Batas interpretasi Tahun Pertama:</strong>
        analitik menggunakan kondisi baseline pada snapshot yang tersedia. Label potensi/persaingan adalah indikasi relatif untuk bahan pertimbangan, bukan prediksi keberhasilan usaha.
    </div>

    @if($data['quality_warning'] ?? false)
        <div class="alert alert-warning">
            <strong>Perhatian mutu data.</strong>
            Terdapat flag mutu pada sebagian record yang digunakan. Nilai sumber tetap dipertahankan dan tidak dinormalisasi atau dibuang; interpretasikan agregat dengan hati-hati.
        </div>
    @endif

    <section class="card border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Konteks Analisis</h2>
                    <p class="text-body-secondary mb-0">Pilih usaha milik Anda, jenis usaha primer, dan wilayah yang ingin dieksplorasi.</p>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('pelaku-umkm.analytics.index') }}">Reset</a>
            </div>

            <form method="GET" action="{{ route('pelaku-umkm.analytics.index') }}" class="row g-3">
                <div class="col-lg-4">
                    <label for="umkm_id" class="form-label">Usaha Saya</label>
                    <select id="umkm_id" name="umkm_id" class="form-select">
                        <option value="">Pilih usaha</option>
                        @foreach($data['owned_umkms'] ?? [] as $owned)
                            <option value="{{ $owned['id'] }}" @selected((int)($selectedUmkm['id'] ?? 0) === (int)$owned['id'])>
                                {{ $owned['business_name'] }} · {{ $owned['umkm_code'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4">
                    <label for="type_id" class="form-label">Jenis Usaha Primer</label>
                    <select id="type_id" name="type_id" class="form-select" @disabled(!$selectedUmkm)>
                        <option value="">Pilih jenis usaha</option>
                        @foreach($data['available_types'] ?? [] as $type)
                            <option value="{{ $type['id'] }}" @selected((int)($selectedType['id'] ?? 0) === (int)$type['id'])>{{ $type['name'] }}</option>
                        @endforeach
                    </select>
                    @if($selectedUmkm && empty($data['available_types']))
                        <div class="form-text text-warning">Belum ada klasifikasi jenis usaha primer yang eksplisit pada sumber.</div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <label for="district_id" class="form-label">Wilayah Analisis Potensi</label>
                    <select id="district_id" name="district_id" class="form-select" @disabled(!$selectedUmkm)>
                        <option value="">Pilih kecamatan</option>
                        @foreach($data['districts'] ?? [] as $district)
                            <option value="{{ $district['id'] }}" @selected((int)($selectedDistrict['id'] ?? 0) === (int)$district['id'])>{{ $district['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary px-4" type="submit">Terapkan Analisis</button>
                </div>
            </form>
        </div>
    </section>

    @if(!$selectedUmkm)
        <div class="card border shadow-sm">
            <div class="card-body p-5 text-center text-body-secondary">
                Pilih salah satu UMKM yang memiliki binding kepemilikan aktif dan terverifikasi untuk memulai analisis.
            </div>
        </div>
    @else
        <section class="card border shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                    <div>
                        <span class="badge text-bg-primary-subtle text-primary-emphasis mb-2">Posisi Usaha Saya</span>
                        <h2 class="h5 mb-1">{{ $selectedUmkm['business_name'] }}</h2>
                        <p class="text-body-secondary mb-0">
                            @if($selectedType)
                                Dibandingkan dengan UMKM lain yang memiliki jenis usaha primer <strong>{{ $selectedType['name'] }}</strong> dalam scope kota.
                            @else
                                Pilih jenis usaha primer untuk membandingkan posisi usaha.
                            @endif
                        </p>
                    </div>
                    <div class="small text-body-secondary">
                        Snapshot: {{ data_get($data, 'freshness.snapshot_id') ?: 'Belum tersedia' }}
                    </div>
                </div>

                @if($position)
                    <div class="row g-3">
                        @foreach($position['metrics'] as $key => $metric)
                            @php
                                $isMoney = in_array($key, ['capital', 'annual_sales', 'monthly_revenue'], true);
                                $ownFormatted = $isMoney ? $money($metric['own_value']) : $number($metric['own_value']);
                                $peerFormatted = $metric['peer_visible']
                                    ? ($isMoney ? $money($metric['peer_median']) : $number($metric['peer_median']))
                                    : 'Dibatasi';
                            @endphp
                            <div class="col-md-6 col-xl-3">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="small text-body-secondary">{{ $metric['label'] }}</div>
                                    <div class="h5 mb-1">{{ $ownFormatted }}</div>
                                    <div class="small">Median peer: <strong>{{ $peerFormatted }}</strong></div>
                                    <div class="small text-body-secondary">n peer terisi: {{ number_format((int)$metric['peer_sample_count'], 0, ',', '.') }}</div>
                                    @if($metric['position'])
                                        <span class="badge text-bg-light border mt-2">{{ $metric['position'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="small text-body-secondary mt-3">
                        Kelompok pembanding mengecualikan seluruh UMKM yang dimiliki akun ini. Agregat peer hanya ditampilkan jika minimal {{ $minimumGroupSize }} record memenuhi rule privasi dan ketersediaan indikator.
                    </div>
                @else
                    <div class="alert alert-light border mb-0">Jenis usaha primer belum dipilih atau data pembanding belum tersedia.</div>
                @endif
            </div>
        </section>

        <section class="card border shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="badge text-bg-info-subtle text-info-emphasis mb-2">Persaingan Usaha Sejenis</span>
                    <h2 class="h5 mb-1">Perbandingan Antarwilayah</h2>
                    <p class="text-body-secondary mb-0">
                        Jumlah usaha adalah indikator kepadatan. Modal dan indikator ekonomi membantu memberi konteks; keduanya tidak digunakan sebagai bukti kausal tentang persaingan.
                    </p>
                </div>

                @if($selectedType && $competition->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Kecamatan</th>
                                    <th>Usaha Sejenis</th>
                                    <th>Kepadatan</th>
                                    <th>Modal (Total / Median)</th>
                                    <th>{{ $competitionSummary['economic_metric_label'] ?? 'Indikator Ekonomi' }} (Total / Median)</th>
                                    <th>Mutu</th>
                                    <th>Interpretasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($competition as $row)
                                    <tr @class(['table-primary' => $row['is_own_district']])>
                                        <td>
                                            <div class="fw-semibold">{{ $row['name'] }}</div>
                                            @if($row['is_own_district'])
                                                <span class="badge text-bg-primary">Wilayah usaha Anda</span>
                                            @endif
                                        </td>
                                        <td style="min-width: 130px;">
                                            <div class="d-flex justify-content-between gap-2 mb-1">
                                                <strong>{{ number_format((int)$row['business_count'], 0, ',', '.') }}</strong>
                                            </div>
                                            <div class="progress" role="progressbar" aria-label="Kepadatan {{ $row['name'] }}" aria-valuenow="{{ (int)$row['business_count'] }}" aria-valuemin="0" aria-valuemax="{{ $maxCompetitionCount }}" style="height: 7px;">
                                                <div class="progress-bar" style="width: {{ $barPercent((int)$row['business_count'], $maxCompetitionCount) }}%"></div>
                                            </div>
                                        </td>
                                        <td><span class="badge {{ $densityBadge($row['density_level']) }}">{{ $row['density_level'] }}</span></td>
                                        <td>
                                            <div>Total: <strong>{{ $aggregateValue($row['capital'] ?? null, $money, 'total') }}</strong></div>
                                            <div class="small">Median: {{ $aggregateValue($row['capital'] ?? null, $money, 'median') }}</div>
                                            <div class="small text-body-secondary">n={{ (int)data_get($row, 'capital.sample_count', 0) }}</div>
                                        </td>
                                        <td>
                                            @php
                                                $economicKey = ($row['economic_metric'] ?? null) === 'annual_sales_amount' ? 'annual_sales' : 'monthly_revenue';
                                            @endphp
                                            <div>Total: <strong>{{ $aggregateValue($row[$economicKey] ?? null, $money, 'total') }}</strong></div>
                                            <div class="small">Median: {{ $aggregateValue($row[$economicKey] ?? null, $money, 'median') }}</div>
                                            <div class="small text-body-secondary">n={{ (int)data_get($row, $economicKey.'.sample_count', 0) }}</div>
                                        </td>
                                        <td>
                                            @if($row['quality_warning'])
                                                <span class="badge text-bg-warning">Ada flag mutu</span>
                                            @else
                                                <span class="badge text-bg-success">Tanpa flag terbuka</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['context_label'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="small text-body-secondary">
                        Q1 jumlah usaha: {{ $competitionSummary['business_count_q1'] !== null ? number_format((float)$competitionSummary['business_count_q1'], 1, ',', '.') : '-' }} ·
                        Q3: {{ $competitionSummary['business_count_q3'] !== null ? number_format((float)$competitionSummary['business_count_q3'], 1, ',', '.') : '-' }} ·
                        Referensi ekonomi: {{ $competitionSummary['economic_metric_label'] ?? 'Belum cukup data' }}.
                    </div>
                @else
                    <div class="alert alert-light border mb-0">Pilih jenis usaha primer untuk melihat perbandingan wilayah.</div>
                @endif
            </div>
        </section>

        <section class="card border shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="badge text-bg-success-subtle text-success-emphasis mb-2">Potensi Jenis Usaha</span>
                    <h2 class="h5 mb-1">Jenis Usaha yang Relatif Sedikit tetapi Menunjukkan Sinyal Ekonomi</h2>
                    <p class="text-body-secondary mb-0">
                        Rule transparan: jumlah usaha berada pada kuartil bawah wilayah dan median indikator ekonomi tidak lebih rendah dari median pembanding wilayah. Tidak ada skor berbobot.
                    </p>
                </div>

                @if(!$selectedDistrict)
                    <div class="alert alert-light border mb-0">Pilih kecamatan untuk menganalisis indikasi potensi jenis usaha.</div>
                @elseif($opportunities->isEmpty())
                    <div class="alert alert-light border mb-0">Belum ada data yang cukup pada {{ $selectedDistrict['name'] }}.</div>
                @else
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-light border">Wilayah: {{ $selectedDistrict['name'] }}</span>
                        <span class="badge text-bg-light border">Q1 jumlah usaha: {{ $opportunitySummary['business_count_q1'] !== null ? number_format((float)$opportunitySummary['business_count_q1'], 1, ',', '.') : '-' }}</span>
                        <span class="badge text-bg-light border">Indikator: {{ $opportunitySummary['economic_metric_label'] ?? 'Belum cukup data' }}</span>
                        <span class="badge text-bg-light border">Indikasi potensi: {{ (int)($opportunitySummary['potential_count'] ?? 0) }}</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Jenis Usaha</th>
                                    <th>Jumlah</th>
                                    <th>Modal (Total / Median)</th>
                                    <th>Indikator Ekonomi (Total / Median)</th>
                                    <th>Mutu</th>
                                    <th>Interpretasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($opportunities as $row)
                                    @php
                                        $economicKey = ($row['economic_metric'] ?? null) === 'annual_sales_amount' ? 'annual_sales' : 'monthly_revenue';
                                    @endphp
                                    <tr @class(['table-success' => $row['potential_relative']])>
                                        <td>
                                            <div class="fw-semibold">{{ $row['type_name'] }}</div>
                                            @if($row['potential_relative'])
                                                <span class="badge text-bg-success">Indikasi potensi relatif</span>
                                            @elseif($row['low_count_group'])
                                                <span class="badge text-bg-light border">Jumlah relatif sedikit</span>
                                            @endif
                                        </td>
                                        <td style="min-width: 130px;">
                                            <div class="d-flex justify-content-between gap-2 mb-1">
                                                <strong>{{ number_format((int)$row['business_count'], 0, ',', '.') }}</strong>
                                            </div>
                                            <div class="progress" role="progressbar" aria-label="Jumlah {{ $row['type_name'] }}" aria-valuenow="{{ (int)$row['business_count'] }}" aria-valuemin="0" aria-valuemax="{{ $maxOpportunityCount }}" style="height: 7px;">
                                                <div class="progress-bar" style="width: {{ $barPercent((int)$row['business_count'], $maxOpportunityCount) }}%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>Total: <strong>{{ $aggregateValue($row['capital'] ?? null, $money, 'total') }}</strong></div>
                                            <div class="small">Median: {{ $aggregateValue($row['capital'] ?? null, $money, 'median') }}</div>
                                            <div class="small text-body-secondary">n={{ (int)data_get($row, 'capital.sample_count', 0) }}</div>
                                        </td>
                                        <td>
                                            <div>Total: <strong>{{ $aggregateValue($row[$economicKey] ?? null, $money, 'total') }}</strong></div>
                                            <div class="small">Median: {{ $aggregateValue($row[$economicKey] ?? null, $money, 'median') }}</div>
                                            <div class="small text-body-secondary">n={{ (int)data_get($row, $economicKey.'.sample_count', 0) }}</div>
                                        </td>
                                        <td>
                                            @if($row['quality_warning'])
                                                <span class="badge text-bg-warning">Ada flag mutu</span>
                                            @else
                                                <span class="badge text-bg-success">Tanpa flag terbuka</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['context_label'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Cara Membaca Hasil</h2>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="border rounded-3 p-3 h-100">
                            <strong>Kepadatan usaha</strong>
                            <p class="small text-body-secondary mb-0">Dihitung dari jumlah UMKM dengan jenis usaha primer yang sama pada asosiasi kecamatan sumber. Bukan ukuran permintaan pasar.</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="border rounded-3 p-3 h-100">
                            <strong>Indikasi potensi relatif</strong>
                            <p class="small text-body-secondary mb-0">Hanya diberikan saat jumlah usaha relatif rendah dan indikator ekonomi kelompok memenuhi rule pembanding baseline. Bukan rekomendasi membuka usaha/cabang.</p>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="border rounded-3 p-3 h-100">
                            <strong>Privasi & mutu</strong>
                            <p class="small text-body-secondary mb-0">Nominal agregat kelompok di bawah n={{ $minimumGroupSize }} disembunyikan. Flag mutu tidak mengubah nilai sumber; hanya memberi peringatan interpretasi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection