@extends('layouts.dashboard')

@section('title', 'Dashboard Admin Dinas')

@section('content')
@php
    $summary = $data['summary'] ?? [];
    $options = $data['filter_options'] ?? [];
    $filters = $data['filters'] ?? [];
    $financial = $data['financial'] ?? null;
    $coverage = $financial['coverage'] ?? [];
    $snapshot = $data['freshness']['snapshot_id'] ?? null;
    $completedAt = $data['freshness']['completed_at'] ?? null;

    $formatMoney = static function ($value): string {
        if ($value === null || $value === '') {
            return 'Belum tersedia';
        }

        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };

    $coveragePercent = static function (int $filled, int $total): string {
        if ($total <= 0) {
            return '0,0%';
        }

        return number_format(($filled / $total) * 100, 1, ',', '.') . '%';
    };
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
                <div>
                    <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-2">Monitoring Internal Dinas</span>
                    <h1 class="h3 mb-2">Monitoring dan Analitik UMKM</h1>
                    <p class="text-body-secondary mb-0">
                        Ruang internal untuk melihat kondisi UMKM secara lebih rinci berdasarkan wilayah, klasifikasi usaha,
                        mutu data, dan ketersediaan informasi keuangan.
                    </p>
                </div>
                <div class="border rounded-3 p-3 bg-body-tertiary align-self-xl-start">
                    <div class="small text-body-secondary">Sumber aktif</div>
                    <strong>{{ $data['freshness']['source_system'] ?? 'LSS' }}</strong>
                    <div class="small text-body-secondary mt-2">Snapshot</div>
                    <strong>{{ $snapshot ?: 'Belum tersedia' }}</strong>
                    <div class="small text-body-secondary mt-2">Sinkron selesai</div>
                    <strong>{{ $completedAt ?: 'Belum tersedia' }}</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Filter Analitik</h2>
                    <p class="text-body-secondary mb-0">Seluruh indikator di bawah mengikuti konteks filter yang sama.</p>
                </div>
                <a href="{{ route('admin-dinas.dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset Filter</a>
            </div>

            <form method="GET" action="{{ route('admin-dinas.dashboard') }}" class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label class="form-label" for="district_id">Kecamatan</label>
                    <select class="form-select" id="district_id" name="district_id">
                        <option value="">Semua Kecamatan</option>
                        @foreach(($options['districts'] ?? []) as $item)
                            <option value="{{ $item->id }}" @selected((string)($filters['district_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label class="form-label" for="village_id">Kelurahan</label>
                    <select class="form-select" id="village_id" name="village_id">
                        <option value="">Semua Kelurahan</option>
                        @foreach(($options['villages'] ?? []) as $item)
                            <option value="{{ $item->id }}" @selected((string)($filters['village_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="category_id">Kategori</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Semua Kategori</option>
                        @foreach(($options['categories'] ?? []) as $item)
                            <option value="{{ $item->id }}" @selected((string)($filters['category_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="type_id">Jenis Usaha</label>
                    <select class="form-select" id="type_id" name="type_id">
                        <option value="">Semua Jenis</option>
                        @foreach(($options['types'] ?? []) as $item)
                            <option value="{{ $item->id }}" @selected((string)($filters['type_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-8 col-xl-2">
                    <label class="form-label" for="marketing_method_id">Pemasaran</label>
                    <select class="form-select" id="marketing_method_id" name="marketing_method_id">
                        <option value="">Semua Metode</option>
                        @foreach(($options['marketingMethods'] ?? []) as $item)
                            <option value="{{ $item->id }}" @selected((string)($filters['marketing_method_id'] ?? '') === (string)$item->id)>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-xl-12 d-flex justify-content-end">
                    <button class="btn btn-primary px-4" type="submit">Tampilkan Analitik</button>
                </div>
            </form>
        </div>
    </section>

    <section>
        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small">UMKM dalam konteks aktif</div>
                        <div class="display-6 fw-semibold">{{ number_format((int)($summary['total_umkm'] ?? 0), 0, ',', '.') }}</div>
                        <div class="small text-body-secondary">Record operasional sesuai filter</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small">Tenaga kerja tercatat</div>
                        <div class="display-6 fw-semibold">{{ number_format((int)($summary['workforce_recorded'] ?? 0), 0, ',', '.') }}</div>
                        <div class="small text-body-secondary">Akumulasi nilai sumber yang terdata</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small">Terasosiasi wilayah</div>
                        <div class="display-6 fw-semibold">{{ number_format((int)($summary['spatial_associated'] ?? 0), 0, ',', '.') }}</div>
                        <div class="small text-body-secondary">
                            Belum terasosiasi: {{ number_format((int)($summary['spatial_unassociated'] ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small">UMKM dengan catatan mutu terbuka</div>
                        <div class="display-6 fw-semibold">{{ number_format((int)($summary['quality_affected'] ?? 0), 0, ',', '.') }}</div>
                        <div class="small text-body-secondary">Catatan mutu tidak berarti data otomatis salah</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-1">Sebaran UMKM per Kecamatan</h2>
                    <p class="text-body-secondary mb-3">Jumlah record operasional yang terasosiasi ke wilayah administratif.</p>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Kecamatan</th>
                                    <th class="text-end">UMKM</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($data['districts'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($row['total_umkm'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-body-secondary">Belum tersedia pada konteks aktif.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-1">Kategori Usaha Dominan</h2>
                    <p class="text-body-secondary mb-3">Sepuluh kategori dengan jumlah UMKM terbanyak pada filter aktif.</p>

                    <div class="d-flex flex-column gap-3">
                        @forelse(($data['categories'] ?? []) as $row)
                            <div class="d-flex justify-content-between gap-3 border-bottom pb-2">
                                <span>{{ $row['name'] }}</span>
                                <strong>{{ number_format($row['total_umkm'], 0, ',', '.') }}</strong>
                            </div>
                        @empty
                            <p class="text-body-secondary mb-0">Belum tersedia pada konteks aktif.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($data['can_view_financial'] ?? false)
        <section class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                    <div>
                        <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis mb-2">Internal · Data Sensitif</span>
                        <h2 class="h4 mb-2">Analitik Keuangan Internal</h2>
                        <p class="text-body-secondary mb-0">
                            Nilai ditampilkan sesuai nilai yang tersimpan dari sumber. Sistem tidak mengalikan, mengoreksi,
                            menormalisasi, atau menyatakan nominal tersebut valid tanpa proses verifikasi.
                        </p>
                    </div>
                    <div class="alert alert-warning mb-0 align-self-lg-start">
                        <strong>0 berbeda dari belum tersedia.</strong><br>
                        Cakupan dihitung berdasarkan nilai non-NULL.
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    @foreach([
                        ['label' => 'Modal terdata', 'key' => 'capital_filled'],
                        ['label' => 'Penjualan tahunan terdata', 'key' => 'annual_sales_filled'],
                        ['label' => 'Omzet bulanan terdata', 'key' => 'monthly_revenue_filled'],
                        ['label' => 'Jumlah pinjaman terdata', 'key' => 'loan_amount_filled'],
                        ['label' => 'Sumber pinjaman terdata', 'key' => 'loan_source_filled'],
                    ] as $metric)
                        @php
                            $filled = (int)($coverage[$metric['key']] ?? 0);
                            $total = (int)($coverage['total_umkm'] ?? 0);
                        @endphp
                        <div class="col-md-6 col-xl">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-body-secondary">{{ $metric['label'] }}</div>
                                <div class="h3 mb-0">{{ number_format($filled, 0, ',', '.') }}</div>
                                <div class="small text-body-secondary">{{ $coveragePercent($filled, $total) }} dari konteks aktif</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row g-4">
                    <div class="col-xl-8">
                        <h3 class="h6">Cakupan Informasi Keuangan per Kecamatan</h3>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Kecamatan</th>
                                        <th class="text-end">UMKM</th>
                                        <th class="text-end">Modal</th>
                                        <th class="text-end">Penjualan</th>
                                        <th class="text-end">Pinjaman</th>
                                        <th class="text-end">Sumber Pinjaman</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($financial['districts'] ?? []) as $row)
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

                    <div class="col-xl-4">
                        <h3 class="h6">Sumber Pinjaman yang Terdata</h3>
                        <div class="d-flex flex-column gap-2">
                            @forelse(($financial['loanSources'] ?? []) as $row)
                                <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                    <span>{{ $row['name'] }}</span>
                                    <strong>{{ number_format($row['total_umkm'], 0, ',', '.') }}</strong>
                                </div>
                            @empty
                                <p class="text-body-secondary">Belum tersedia.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div>
                    <h3 class="h5 mb-1">Detail Nilai Keuangan Terdata</h3>
                    <p class="text-body-secondary">
                        Menampilkan maksimal 30 UMKM pada konteks aktif. Nilai kecil atau tidak lazim tetap ditampilkan apa adanya
                        dan tidak diubah menjadi satuan lain.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>UMKM</th>
                                    <th>Kecamatan</th>
                                    <th class="text-end">Modal</th>
                                    <th class="text-end">Penjualan Tahunan</th>
                                    <th class="text-end">Omzet Bulanan</th>
                                    <th class="text-end">Pinjaman</th>
                                    <th>Sumber Pinjaman</th>
                                    <th>Mutu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(collect($financial['details'] ?? [])->take(8) as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $row['business_name'] }}</div>
                                            <div class="small text-body-secondary">{{ $row['umkm_code'] }}</div>
                                        </td>
                                        <td>{{ $row['district_name'] ?? 'Belum terasosiasi' }}</td>
                                        <td class="text-end">{{ $formatMoney($row['capital_amount']) }}</td>
                                        <td class="text-end">{{ $formatMoney($row['annual_sales_amount']) }}</td>
                                        <td class="text-end">{{ $formatMoney($row['baseline_monthly_revenue']) }}</td>
                                        <td class="text-end">{{ $formatMoney($row['loan_amount']) }}</td>
                                        <td>{{ ($row['loan_source'] ?? '') !== '' ? $row['loan_source'] : 'Belum tersedia' }}</td>
                                        <td>{{ $row['quality_status'] ?: 'Belum tersedia' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-body-secondary">Tidak ada data pada konteks aktif.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5">Akses Analitik Keuangan Belum Aktif</h2>
                <p class="text-body-secondary mb-0">
                    Informasi keuangan merupakan data internal sensitif dan hanya ditampilkan kepada akun yang memiliki izin
                    <code>umkm.sensitive.financial</code>.
                </p>
            </div>
        </section>
    @endif
</div>
@endsection