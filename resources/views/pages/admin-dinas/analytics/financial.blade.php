@extends('layouts.dashboard')

@section('title', 'Analitik Keuangan Internal')

@section('content')
@php
    $filters = $data['filters'] ?? [];
    $options = $data['filter_options'] ?? [];
    $coverage = $data['financial']['coverage'] ?? [];
    $analysis = $data['loan_source_analysis'] ?? [];
    $financial = $data['financial'] ?? [];
    $financialRows = $financial['details'] ?? null;

    $baseFilter = array_filter($filters, static fn ($value) => $value !== null && $value !== '');

    $money = static fn ($value): string => ($value === null || $value === '')
        ? 'Belum tersedia'
        : 'Rp ' . number_format((float)$value, 0, ',', '.');

    $label = static fn ($value): string => $value ? \Illuminate\Support\Str::headline((string)$value) : 'Belum tersedia';

    $pctCoverage = static function (int $filled, int $total): string {
        if ($total <= 0) {
            return '0,0%';
        }

        return number_format(($filled / $total) * 100, 1, ',', '.') . '%';
    };

    $barPct = static function (int $value, int $max): string {
        if ($max <= 0) {
            return '0';
        }

        return number_format(min(100, ($value / $max) * 100), 2, '.', '');
    };

    $maxIdentified = max(1, (int)(collect($analysis['identified'] ?? [])->max('total_umkm') ?? 1));
    $maxIssue = max(1, (int)(collect($analysis['quality_issues'] ?? [])->max('total_umkm') ?? 1));
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border shadow-sm">
        <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis mb-2">Internal · Data Sensitif</span>
                <h1 class="h3 mb-2">Analitik Ekonomi & Keuangan</h1>
                <p class="text-body-secondary mb-0">
                    Analitik berfokus pada cakupan dan distribusi nilai yang terdata. Sistem tidak mengoreksi satuan, menormalisasi nominal, atau membuat total/rata-rata nominal sebagai indikator kebijakan.
                </p>
            </div>
            <div class="d-flex gap-2 align-self-lg-start">
                <a href="{{ route('admin-dinas.analytics.index', $baseFilter) }}" class="btn btn-outline-primary">Analitik Umum</a>
                <a href="{{ route('admin-dinas.umkm.index', $baseFilter) }}" class="btn btn-outline-secondary">Data UMKM</a>
            </div>
        </div>
    </section>

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin-dinas.analytics.financial') }}" class="row g-3">
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
                            <option value="{{ $value }}" @selected((string)($filters['quality_status'] ?? '') === (string)$value)>{{ $label($value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-auto">
                    <label class="form-label" for="per_page">Baris</label>
                    <select class="form-select" id="per_page" name="per_page">
                        @foreach([25, 50, 100] as $pageSize)
                            <option value="{{ $pageSize }}" @selected((int)($filters['per_page'] ?? 25) === $pageSize)>{{ $pageSize }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin-dinas.analytics.financial') }}" class="btn btn-outline-secondary">Reset</a>
                    <button class="btn btn-warning" type="submit">Terapkan</button>
                </div>
            </form>
        </div>
    </section>

    @include('pages.admin-dinas.partials.active-context', [
        'contextFilters' => $filters,
        'contextOptions' => $options,
        'contextCount' => $coverage['total_umkm'] ?? 0,
    ])

    <section class="alert alert-warning mb-0">
        <strong>0 berbeda dari belum tersedia.</strong>
        Cakupan menggunakan nilai non-NULL. Nilai kecil atau tidak lazim tetap dipertahankan sebagai nilai sumber sampai ada verifikasi.
    </section>

    <section class="row g-3">
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
                <div class="card border shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-body-secondary">{{ $metric[0] }} terdata</div>
                        <div class="h2 mb-0">{{ number_format($filled, 0, ',', '.') }}</div>
                        <div class="small text-body-secondary">{{ $pctCoverage($filled, $total) }} dari konteks aktif</div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="card border shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5">Cakupan Keuangan per Kecamatan</h2>
            <p class="text-body-secondary">Tabel menunjukkan jumlah UMKM dengan nilai terdata pada masing-masing unsur keuangan.</p>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Kecamatan</th>
                            <th class="text-end">UMKM</th>
                            <th class="text-end">Modal</th>
                            <th class="text-end">Penjualan</th>
                            <th class="text-end">Pinjaman</th>
                            <th class="text-end">Sumber</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($financial['districts'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['total_umkm'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['capital_filled'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['annual_sales_filled'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['loan_amount_filled'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['loan_source_filled'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-body-secondary">Belum tersedia.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-xl-6">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5">Sumber Pinjaman Teridentifikasi</h2>
                    <p class="text-body-secondary">Nilai sumber ditampilkan apa adanya dan tidak dinormalisasi menjadi kategori lain.</p>

                    <div class="d-flex flex-column gap-3">
                        @forelse($analysis['identified'] ?? [] as $row)
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $row['raw_value'] }}</span>
                                    <strong>{{ number_format($row['total_umkm'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar bg-success" style="width: {{ $barPct((int)$row['total_umkm'], $maxIdentified) }}%"></div></div>
                            </div>
                        @empty
                            <p class="text-body-secondary">Belum tersedia.</p>
                        @endforelse
                    </div>

                    <div class="small text-body-secondary mt-3">Belum tersedia: {{ number_format((int)($analysis['missing'] ?? 0), 0, ',', '.') }} UMKM</div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card border-warning-subtle shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5">Catatan Mutu Sumber Pinjaman</h2>
                    <p class="text-body-secondary">
                        String sumber yang memuat marker “data keuangan tidak tersedia” dipisahkan sebagai quality issue. Nilai tersebut tidak diubah menjadi Mekaar, KUR, atau kategori lain.
                    </p>

                    <div class="d-flex flex-column gap-3">
                        @forelse($analysis['quality_issues'] ?? [] as $row)
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $row['raw_value'] }}</span>
                                    <strong>{{ number_format($row['total_umkm'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="progress" style="height: 8px;"><div class="progress-bar bg-warning" style="width: {{ $barPct((int)$row['total_umkm'], $maxIssue) }}%"></div></div>
                            </div>
                        @empty
                            <p class="text-body-secondary">Tidak ada marker tersebut pada konteks aktif.</p>
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
                    <h2 class="h5 mb-1">Preview Nilai Keuangan Terdata</h2>
                    @if($financialRows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                        <p class="text-body-secondary mb-0">
                            Menampilkan {{ number_format($financialRows->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($financialRows->lastItem() ?? 0, 0, ',', '.') }}
                            dari {{ number_format($financialRows->total(), 0, ',', '.') }} record. Nilai sumber tidak dikoreksi.
                        </p>
                    @else
                        <p class="text-body-secondary mb-0">Nilai sumber ditampilkan tanpa koreksi.</p>
                    @endif
                </div>
                <a href="{{ route('admin-dinas.umkm.index', array_filter($filters, static fn ($value) => $value !== null && $value !== '')) }}" class="btn btn-outline-primary align-self-lg-start">Lihat Data UMKM</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>UMKM</th>
                            <th>Kecamatan</th>
                            <th class="text-end">Modal</th>
                            <th class="text-end">Penjualan</th>
                            <th class="text-end">Pinjaman</th>
                            <th>Sumber</th>
                            <th>Mutu</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($financialRows ?? []) as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $row['business_name'] }}</div>
                                    <div class="small text-body-secondary">{{ $row['umkm_code'] }}</div>
                                </td>
                                <td>{{ $row['district_name'] ?? 'Belum terasosiasi' }}</td>
                                <td class="text-end">{{ $money($row['capital_amount']) }}</td>
                                <td class="text-end">{{ $money($row['annual_sales_amount']) }}</td>
                                <td class="text-end">{{ $money($row['loan_amount']) }}</td>
                                <td>{{ ($row['loan_source'] ?? '') !== '' ? $row['loan_source'] : 'Belum tersedia' }}</td>
                                <td>{{ $label($row['quality_status']) }}</td>
                                <td><a href="{{ route('admin-dinas.umkm.show', $row['id']) }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-body-secondary">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($financialRows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                @php
                    $financialCurrentPage = $financialRows->currentPage();
                    $financialLastPage = $financialRows->lastPage();

                    $financialPageNumbers = collect([
                        1,
                        $financialCurrentPage - 2,
                        $financialCurrentPage - 1,
                        $financialCurrentPage,
                        $financialCurrentPage + 1,
                        $financialCurrentPage + 2,
                        $financialLastPage,
                    ])->filter(static fn ($page) => $page >= 1 && $page <= $financialLastPage)
                      ->unique()
                      ->sort()
                      ->values();
                @endphp

                <div class="border-top pt-3 mt-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div class="small text-body-secondary">
                            Halaman {{ number_format($financialCurrentPage, 0, ',', '.') }}
                            dari {{ number_format($financialLastPage, 0, ',', '.') }}
                        </div>

                        @if($financialLastPage > 1)
                            <nav aria-label="Navigasi Halaman Data Keuangan UMKM">
                                <ul class="pagination pagination-sm mb-0 flex-wrap">
                                    <li class="page-item @if($financialRows->onFirstPage()) disabled @endif">
                                        <a class="page-link" href="{{ $financialRows->previousPageUrl() ?: '#' }}">Sebelumnya</a>
                                    </li>

                                    @php $financialPreviousRendered = null; @endphp
                                    @foreach($financialPageNumbers as $page)
                                        @if($financialPreviousRendered !== null && $page - $financialPreviousRendered > 1)
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        @endif

                                        <li class="page-item @if($page === $financialCurrentPage) active @endif">
                                            <a class="page-link" href="{{ $financialRows->url($page) }}">{{ number_format($page, 0, ',', '.') }}</a>
                                        </li>

                                        @php $financialPreviousRendered = $page; @endphp
                                    @endforeach

                                    <li class="page-item @if(!$financialRows->hasMorePages()) disabled @endif">
                                        <a class="page-link" href="{{ $financialRows->nextPageUrl() ?: '#' }}">Berikutnya</a>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
<script src="{{ asset('assets/js/pages/admin-dinas/admin-dinas-region-cascade.js') }}" defer></script>
@endsection
