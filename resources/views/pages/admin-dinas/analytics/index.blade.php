@extends('layouts.dashboard')

@section('title', 'Analitik Internal Admin Dinas')

@section('content')
@php
    $filters = $data['filters'] ?? [];
    $options = $data['filter_options'] ?? [];
    $summary = $data['summary'] ?? [];
    $label = static fn ($value): string => $value ? \Illuminate\Support\Str::headline((string)$value) : 'Belum tersedia';
    $baseFilter = array_filter($filters, static fn ($value) => $value !== null && $value !== '');

    $pct = static function (int $value, int $max): string {
        if ($max <= 0) {
            return '0';
        }

        return number_format(min(100, ($value / $max) * 100), 2, '.', '');
    };

    $maxDistrict = max(1, (int)(collect($data['districts'] ?? [])->max('total_umkm') ?? 1));
    $maxCategory = max(1, (int)(collect($data['categories'] ?? [])->max('total_umkm') ?? 1));
    $maxType = max(1, (int)(collect($data['types'] ?? [])->max('total_umkm') ?? 1));
    $maxWorkers = max(1, (int)(collect($data['workforce_by_district'] ?? [])->max('total_workers') ?? 1));
    $maxMarketing = max(1, (int)(collect($data['marketing_methods'] ?? [])->max('total_umkm') ?? 1));
    $maxQuality = max(1, (int)(collect($data['quality_groups'] ?? [])->max('affected_umkm') ?? 1));
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border-0 shadow-sm">
        <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-2">Visual Analytics Internal</span>
                <h1 class="h3 mb-2">Analitik UMKM Admin Dinas</h1>
                <p class="text-body-secondary mb-0">Klik visual untuk melakukan drill-down ke Data UMKM dengan konteks yang sama.</p>
            </div>
            <div class="d-flex gap-2 align-self-lg-start">
                <a href="{{ route('admin-dinas.umkm.index', $baseFilter) }}" class="btn btn-outline-primary">Data UMKM</a>
                <a href="{{ route('admin-dinas.analytics.spatial', $baseFilter) }}" class="btn btn-success">Peta Wilayah</a>
                @if($data['can_view_financial'] ?? false)
                    <a href="{{ route('admin-dinas.analytics.financial', $baseFilter) }}" class="btn btn-warning">Ekonomi & Keuangan</a>
                @endif
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin-dinas.analytics.index') }}" class="row g-3">
                @foreach([
                    ['district_id', 'Kecamatan', $options['districts'] ?? []],
                    ['category_id', 'Kategori', $options['categories'] ?? []],
                    ['type_id', 'Jenis Usaha', $options['types'] ?? []],
                    ['marketing_method_id', 'Pemasaran', $options['marketingMethods'] ?? []],
                ] as $filter)
                    <div class="col-md-6 col-xl">
                        <label class="form-label" for="{{ $filter[0] }}">{{ $filter[1] }}</label>
                        <select class="form-select" id="{{ $filter[0] }}" name="{{ $filter[0] }}">
                            <option value="">Semua</option>
                            @foreach($filter[2] as $item)
                                <option value="{{ $item->id }}" @selected((string)($filters[$filter[0]] ?? '') === (string)$item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="col-md-8 col-xl">
                    <label class="form-label" for="quality_status">Mutu Data</label>
                    <select class="form-select" id="quality_status" name="quality_status">
                        <option value="">Semua</option>
                        @foreach(($options['qualityStatuses'] ?? []) as $value)
                            <option value="{{ $value }}" @selected((string)($filters['quality_status'] ?? '') === (string)$value)>{{ $label($value) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-xl-auto d-flex align-items-end gap-2">
                    <a href="{{ route('admin-dinas.analytics.index') }}" class="btn btn-outline-secondary">Reset</a>
                    <button class="btn btn-primary" type="submit">Terapkan</button>
                </div>
            </form>
        </div>
    </section>

    <section class="row g-3">
        @foreach([
            ['UMKM', $summary['total_umkm'] ?? 0, 'Record operasional'],
            ['Tenaga Kerja', $summary['workforce_recorded'] ?? 0, 'Nilai sumber terdata'],
            ['Terasosiasi Wilayah', $summary['spatial_associated'] ?? 0, 'Asosiasi administratif'],
            ['NIB Teridentifikasi', $data['legality_identified'] ?? 0, 'Bukan validasi legal formal'],
            ['UMKM dengan Flag Mutu', $summary['quality_affected'] ?? 0, 'Flag terbuka'],
        ] as $metric)
            <div class="col-md-6 col-xl">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-body-secondary">{{ $metric[0] }}</div>
                        <div class="h2 mb-1">{{ number_format((int)$metric[1], 0, ',', '.') }}</div>
                        <div class="small text-body-secondary">{{ $metric[2] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="row g-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5">Profil Sektor · Kecamatan</h2>
                    <p class="text-body-secondary">Distribusi UMKM yang terasosiasi ke kecamatan.</p>

                    <div class="d-flex flex-column gap-3">
                        @forelse($data['districts'] as $row)
                            <a class="text-decoration-none text-body" href="{{ route('admin-dinas.umkm.index', array_merge($baseFilter, ['district_id' => $row['id']])) }}">
                                <div class="d-flex justify-content-between mb-1"><span>{{ $row['name'] }}</span><strong>{{ number_format($row['total_umkm'], 0, ',', '.') }}</strong></div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar bg-success" style="width: {{ $pct((int)$row['total_umkm'], $maxDistrict) }}%"></div></div>
                            </a>
                        @empty
                            <p class="text-body-secondary">Belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5">Profil Sektor · Kategori</h2>
                    <p class="text-body-secondary">Kategori berasal dari klasifikasi yang tersimpan.</p>

                    <div class="d-flex flex-column gap-3">
                        @forelse($data['categories'] as $row)
                            <a class="text-decoration-none text-body" href="{{ route('admin-dinas.umkm.index', array_merge($baseFilter, ['category_id' => $row['id']])) }}">
                                <div class="d-flex justify-content-between mb-1"><span>{{ $row['name'] }}</span><strong>{{ number_format($row['total_umkm'], 0, ',', '.') }}</strong></div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar bg-success" style="width: {{ $pct((int)$row['total_umkm'], $maxCategory) }}%"></div></div>
                            </a>
                        @empty
                            <p class="text-body-secondary">Belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5">Jenis Usaha</h2>
                    <div class="d-flex flex-column gap-3">
                        @forelse($data['types'] as $row)
                            <a class="text-decoration-none text-body" href="{{ route('admin-dinas.umkm.index', array_merge($baseFilter, ['type_id' => $row['id']])) }}">
                                <div class="d-flex justify-content-between mb-1"><span>{{ $row['name'] }}</span><strong>{{ number_format($row['total_umkm'], 0, ',', '.') }}</strong></div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar bg-success" style="width: {{ $pct((int)$row['total_umkm'], $maxType) }}%"></div></div>
                            </a>
                        @empty
                            <p class="text-body-secondary">Belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5">Tenaga Kerja per Kecamatan</h2>
                    <p class="text-body-secondary">Akumulasi employee_count yang terdata pada sumber.</p>

                    <div class="d-flex flex-column gap-3">
                        @forelse($data['workforce_by_district'] as $row)
                            <a class="text-decoration-none text-body" href="{{ route('admin-dinas.umkm.index', array_merge($baseFilter, ['district_id' => $row['id']])) }}">
                                <div class="d-flex justify-content-between mb-1"><span>{{ $row['name'] }}</span><strong>{{ number_format($row['total_workers'], 0, ',', '.') }}</strong></div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar bg-success" style="width: {{ $pct((int)$row['total_workers'], $maxWorkers) }}%"></div></div>
                            </a>
                        @empty
                            <p class="text-body-secondary">Belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5">Akses Pasar · Metode Pemasaran</h2>
                    <div class="d-flex flex-column gap-3">
                        @forelse($data['marketing_methods'] as $row)
                            <a class="text-decoration-none text-body" href="{{ route('admin-dinas.umkm.index', array_merge($baseFilter, ['marketing_method_id' => $row['id']])) }}">
                                <div class="d-flex justify-content-between mb-1"><span>{{ $row['name'] }}</span><strong>{{ number_format($row['total_umkm'], 0, ',', '.') }}</strong></div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar bg-success" style="width: {{ $pct((int)$row['total_umkm'], $maxMarketing) }}%"></div></div>
                            </a>
                        @empty
                            <p class="text-body-secondary">Belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5">Mutu Data · Kelompok Flag</h2>
                    <p class="text-body-secondary mb-2">Flag merupakan hasil pemeriksaan mutu dan bukan koreksi otomatis.</p>
                    <div class="alert alert-info py-2 small">
                        Satu UMKM dapat memiliki lebih dari satu kelompok flag. Jumlah antarkelompok tidak dijumlahkan sebagai total UMKM.
                    </div>

                    <div class="d-flex flex-column gap-3">
                        @forelse($data['quality_groups'] as $row)
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $label($row['name']) }}</span>
                                    <strong>{{ number_format($row['affected_umkm'], 0, ',', '.') }} UMKM</strong>
                                </div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar bg-warning" style="width: {{ $pct((int)$row['affected_umkm'], $maxQuality) }}%"></div></div>
                                <div class="small text-body-secondary mt-1">{{ number_format($row['flag_count'], 0, ',', '.') }} flag terbuka</div>
                            </div>
                        @empty
                            <p class="text-body-secondary">Belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
