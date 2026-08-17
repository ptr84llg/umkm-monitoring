@extends('layouts.dashboard')

@section('title', 'Dashboard Admin Dinas')

@section('content')
@php
    $summary = $data['summary'] ?? [];
    $options = $data['filter_options'] ?? [];
    $filters = $data['filters'] ?? [];
    $financial = $data['financial'] ?? null;
    $coverage = $financial['coverage'] ?? [];
    $districts = collect($data['districts'] ?? [])->take(5);
    $categories = collect($data['categories'] ?? [])->take(5);
    $snapshot = $data['freshness']['snapshot_id'] ?? null;
    $completedAt = $data['freshness']['completed_at'] ?? null;

    $baseFilter = array_filter([
        'district_id' => $filters['district_id'] ?? null,
        'village_id' => $filters['village_id'] ?? null,
        'category_id' => $filters['category_id'] ?? null,
        'type_id' => $filters['type_id'] ?? null,
        'marketing_method_id' => $filters['marketing_method_id'] ?? null,
        'quality_status' => $filters['quality_status'] ?? null,
    ], static fn ($value) => $value !== null && $value !== '');

    $maxDistrict = max(1, (int) ($districts->max('total_umkm') ?? 1));
    $maxCategory = max(1, (int) ($categories->max('total_umkm') ?? 1));

    $pct = static function (int $value, int $max): string {
        if ($max <= 0) {
            return '0';
        }

        return number_format(min(100, ($value / $max) * 100), 2, '.', '');
    };

    $coveragePercent = static function (int $filled, int $total): string {
        if ($total <= 0) {
            return '0,0%';
        }

        return number_format(($filled / $total) * 100, 1, ',', '.') . '%';
    };
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
                <div>
                    <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-2">Ringkasan Kendali Internal</span>
                    <h1 class="h3 mb-2">Monitoring UMKM Admin Dinas</h1>
                    <p class="text-body-secondary mb-0">
                        Dashboard menampilkan indikator kendali utama. Eksplorasi record dan analisis rinci ditempatkan pada modul Data UMKM dan Analitik.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-self-xl-start">
                    <a class="btn btn-outline-primary" href="{{ route('admin-dinas.umkm.index', $baseFilter) }}">Buka Data UMKM</a>
                    <a class="btn btn-primary" href="{{ route('admin-dinas.analytics.index', $baseFilter) }}">Buka Analitik</a>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 bg-body-tertiary">
                        <div class="small text-body-secondary">Sumber aktif</div>
                        <strong>{{ $data['freshness']['source_system'] ?? 'LSS' }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 bg-body-tertiary">
                        <div class="small text-body-secondary">Snapshot</div>
                        <strong class="text-break">{{ $snapshot ?: 'Belum tersedia' }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 bg-body-tertiary">
                        <div class="small text-body-secondary">Sinkron selesai</div>
                        <strong>{{ $completedAt ?: 'Belum tersedia' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Konteks Monitoring</h2>
                    <p class="text-body-secondary mb-0">KPI dan ringkasan di bawah mengikuti filter yang sama.</p>
                </div>
                <a href="{{ route('admin-dinas.dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset Filter</a>
            </div>

            <form method="GET" action="{{ route('admin-dinas.dashboard') }}" class="row g-3">
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
                                <option value="{{ $item->id }}" data-region-code="{{ $item->code ?? '' }}" data-parent-code="{{ $item->parent_code ?? '' }}" @selected((string)($filters[$filter[0]] ?? '') === (string)$item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-md-6 col-xl">
                    <label class="form-label" for="quality_status">Mutu Data</label>
                    <select class="form-select" id="quality_status" name="quality_status">
                        <option value="">Semua</option>
                        @foreach(($options['qualityStatuses'] ?? []) as $value)
                            <option value="{{ $value }}" @selected((string)($filters['quality_status'] ?? '') === (string)$value)>{{ \Illuminate\Support\Str::headline((string)$value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin-dinas.dashboard') }}" class="btn btn-outline-secondary">Reset</a>
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
            ['UMKM dalam konteks aktif', $summary['total_umkm'] ?? 0, 'Record operasional sesuai filter'],
            ['Tenaga kerja tercatat', $summary['workforce_recorded'] ?? 0, 'Akumulasi nilai sumber yang terdata'],
            ['Terasosiasi wilayah', $summary['spatial_associated'] ?? 0, 'Belum terasosiasi: ' . number_format((int)($summary['spatial_unassociated'] ?? 0), 0, ',', '.')],
            ['UMKM dengan flag mutu', $summary['quality_affected'] ?? 0, 'Flag mutu tidak otomatis berarti nilai sumber salah'],
        ] as $metric)
            <div class="col-md-6 col-xl-3">
                <div class="card border shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-body-secondary">{{ $metric[0] }}</div>
                        <div class="display-6 fw-semibold">{{ number_format((int)$metric[1], 0, ',', '.') }}</div>
                        <div class="small text-body-secondary">{{ $metric[2] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="row g-4">
        <div class="col-xl-7">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Fokus Wilayah</h2>
                            <p class="text-body-secondary mb-0">Lima kecamatan dengan jumlah UMKM terbesar pada konteks aktif.</p>
                        </div>
                        <a href="{{ route('admin-dinas.analytics.index', $baseFilter) }}" class="btn btn-sm btn-outline-primary align-self-start">Lihat Analitik</a>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        @forelse($districts as $row)
                            <a class="text-decoration-none text-body" href="{{ route('admin-dinas.umkm.index', array_merge($baseFilter, ['district_id' => $row['id']])) }}">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <span>{{ $row['name'] }}</span>
                                    <strong>{{ number_format((int)$row['total_umkm'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="progress" role="progressbar" aria-label="{{ $row['name'] }}" aria-valuenow="{{ $row['total_umkm'] }}" aria-valuemin="0" aria-valuemax="{{ $maxDistrict }}" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $pct((int)$row['total_umkm'], $maxDistrict) }}%"></div>
                                </div>
                            </a>
                        @empty
                            <p class="text-body-secondary mb-0">Belum tersedia pada konteks aktif.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-1">Fokus Sektor</h2>
                    <p class="text-body-secondary mb-3">Lima kategori dengan jumlah UMKM terbesar pada konteks aktif.</p>

                    <div class="d-flex flex-column gap-3">
                        @forelse($categories as $row)
                            <a class="text-decoration-none text-body" href="{{ route('admin-dinas.umkm.index', array_merge($baseFilter, ['category_id' => $row['id']])) }}">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <span>{{ $row['name'] }}</span>
                                    <strong>{{ number_format((int)$row['total_umkm'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="progress" role="progressbar" aria-label="{{ $row['name'] }}" aria-valuenow="{{ $row['total_umkm'] }}" aria-valuemin="0" aria-valuemax="{{ $maxCategory }}" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $pct((int)$row['total_umkm'], $maxCategory) }}%"></div>
                                </div>
                            </a>
                        @empty
                            <p class="text-body-secondary mb-0">Belum tersedia pada konteks aktif.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($data['can_view_financial'] ?? false)
        <section class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                    <div>
                        <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis mb-2">Internal · Data Sensitif</span>
                        <h2 class="h5 mb-1">Cakupan Keuangan</h2>
                        <p class="text-body-secondary mb-0">
                            Ringkasan hanya menunjukkan ketersediaan nilai. Nominal rinci dan quality issue dianalisis pada halaman Ekonomi & Keuangan.
                        </p>
                    </div>
                    <a href="{{ route('admin-dinas.analytics.financial', $baseFilter) }}" class="btn btn-warning align-self-lg-start">Buka Ekonomi & Keuangan</a>
                </div>

                <div class="alert alert-warning py-2">
                    <strong>0 berbeda dari belum tersedia.</strong> Cakupan dihitung berdasarkan nilai non-NULL dan tidak menyatakan nominal telah tervalidasi.
                </div>

                <div class="row g-3">
                    @foreach([
                        ['Modal', 'capital_filled'],
                        ['Penjualan Tahunan', 'annual_sales_filled'],
                        ['Omzet Bulanan', 'monthly_revenue_filled'],
                        ['Jumlah Pinjaman', 'loan_amount_filled'],
                        ['Sumber Pinjaman', 'loan_source_filled'],
                    ] as $metric)
                        @php
                            $filled = (int)($coverage[$metric[1]] ?? 0);
                            $total = (int)($coverage['total_umkm'] ?? 0);
                        @endphp
                        <div class="col-md-6 col-xl">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-body-secondary">{{ $metric[0] }} terdata</div>
                                <div class="h3 mb-0">{{ number_format($filled, 0, ',', '.') }}</div>
                                <div class="small text-body-secondary">{{ $coveragePercent($filled, $total) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="row g-3">
        <div class="col-lg-4">
            <a href="{{ route('admin-dinas.umkm.index', $baseFilter) }}" class="card border shadow-sm h-100 text-decoration-none text-body">
                <div class="card-body p-4">
                    <span class="badge text-bg-primary-subtle text-primary-emphasis mb-2">Drill-down</span>
                    <h2 class="h5">Data UMKM</h2>
                    <p class="text-body-secondary mb-0">Cari, filter, dan buka detail record UMKM secara read-only.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('admin-dinas.analytics.index', $baseFilter) }}" class="card border shadow-sm h-100 text-decoration-none text-body">
                <div class="card-body p-4">
                    <span class="badge text-bg-success-subtle text-success-emphasis mb-2">Eksplorasi</span>
                    <h2 class="h5">Analitik Internal</h2>
                    <p class="text-body-secondary mb-0">Bandingkan wilayah, sektor, tenaga kerja, pemasaran, legalitas, dan mutu data.</p>
                </div>
            </a>
        </div>
        <div class="col-lg-4">
            @if($data['can_view_financial'] ?? false)
                <a href="{{ route('admin-dinas.analytics.financial', $baseFilter) }}" class="card border shadow-sm h-100 text-decoration-none text-body">
                    <div class="card-body p-4">
                        <span class="badge text-bg-warning-subtle text-warning-emphasis mb-2">Sensitif</span>
                        <h2 class="h5">Ekonomi & Keuangan</h2>
                        <p class="text-body-secondary mb-0">Telusuri cakupan nilai keuangan dan source-quality issue tanpa normalisasi.</p>
                    </div>
                </a>
            @else
                <div class="card border shadow-sm h-100">
                    <div class="card-body p-4">
                        <span class="badge text-bg-secondary mb-2">Terbatas</span>
                        <h2 class="h5">Ekonomi & Keuangan</h2>
                        <p class="text-body-secondary mb-0">Akses memerlukan izin data keuangan sensitif.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
<script src="{{ asset('assets/js/pages/admin-dinas/admin-dinas-region-cascade.js') }}" defer></script>
@endsection
