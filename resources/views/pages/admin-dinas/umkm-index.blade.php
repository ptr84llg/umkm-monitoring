@extends('layouts.dashboard')

@section('title', 'Data UMKM Internal')

@section('content')
@php
    $rows = $data['rows'];
    $filters = $data['filters'] ?? [];
    $options = $data['filter_options'] ?? [];
    $canFinancial = (bool)($data['can_view_financial'] ?? false);

    $label = static fn ($value): string => $value
        ? \Illuminate\Support\Str::headline((string)$value)
        : 'Belum tersedia';

    $money = static fn ($value): string => ($value === null || $value === '')
        ? 'Belum tersedia'
        : 'Rp ' . number_format((float)$value, 0, ',', '.');

    $advancedActive = ! empty($filters['village_id'])
        || ! empty($filters['type_id'])
        || ! empty($filters['marketing_method_id']);

    $currentPage = $rows->currentPage();
    $lastPage = $rows->lastPage();

    $pageNumbers = collect([
        1,
        $currentPage - 2,
        $currentPage - 1,
        $currentPage,
        $currentPage + 1,
        $currentPage + 2,
        $lastPage,
    ])->filter(static fn ($page) => $page >= 1 && $page <= $lastPage)
      ->unique()
      ->sort()
      ->values();
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border-0 shadow-sm">
        <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-2">Read-only Internal</span>
                <h1 class="h3 mb-2">Data UMKM Internal</h1>
                <p class="text-body-secondary mb-0">
                    Penelusuran record tanpa mengubah nilai sumber. Detail keuangan hanya muncul sesuai permission pengguna.
                </p>
            </div>
            <div class="d-flex gap-2 align-self-lg-start">
                <a href="{{ route('admin-dinas.dashboard') }}" class="btn btn-outline-secondary">Dashboard</a>
                <a href="{{ route('admin-dinas.analytics.index') }}" class="btn btn-primary">Buka Analitik</a>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin-dinas.umkm.index') }}" class="row g-3">
                <div class="col-12 col-xl-4">
                    <label class="form-label" for="search">Cari UMKM</label>
                    <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama, kode, atau source record ID">
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="district_id">Kecamatan</label>
                    <select class="form-select" id="district_id" name="district_id">
                        <option value="">Semua</option>
                        @foreach(($options['districts'] ?? []) as $item)
                            <option value="{{ $item->id }}" @selected((string)($filters['district_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="category_id">Kategori</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Semua</option>
                        @foreach(($options['categories'] ?? []) as $item)
                            <option value="{{ $item->id }}" @selected((string)($filters['category_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="quality_status">Mutu Data</label>
                    <select class="form-select" id="quality_status" name="quality_status">
                        <option value="">Semua</option>
                        @foreach(($options['qualityStatuses'] ?? []) as $value)
                            <option value="{{ $value }}" @selected((string)($filters['quality_status'] ?? '') === (string)$value)>{{ $label($value) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label" for="per_page">Baris</label>
                    <select class="form-select" id="per_page" name="per_page">
                        @foreach([25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int)($filters['per_page'] ?? 25) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <details class="border rounded-3 p-3 bg-body-tertiary" @if($advancedActive) open @endif>
                        <summary class="fw-semibold">Filter Lanjutan</summary>
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label" for="village_id">Kelurahan</label>
                                <select class="form-select" id="village_id" name="village_id">
                                    <option value="">Semua Kelurahan</option>
                                    @foreach(($options['villages'] ?? []) as $item)
                                        <option value="{{ $item->id }}" @selected((string)($filters['village_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="type_id">Jenis Usaha</label>
                                <select class="form-select" id="type_id" name="type_id">
                                    <option value="">Semua Jenis</option>
                                    @foreach(($options['types'] ?? []) as $item)
                                        <option value="{{ $item->id }}" @selected((string)($filters['type_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="marketing_method_id">Metode Pemasaran</label>
                                <select class="form-select" id="marketing_method_id" name="marketing_method_id">
                                    <option value="">Semua Metode</option>
                                    @foreach(($options['marketingMethods'] ?? []) as $item)
                                        <option value="{{ $item->id }}" @selected((string)($filters['marketing_method_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </details>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin-dinas.umkm.index') }}" class="btn btn-outline-secondary">Reset</a>
                    <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Daftar UMKM</h2>
                    <p class="text-body-secondary mb-0">
                        Menampilkan {{ number_format($rows->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($rows->lastItem() ?? 0, 0, ',', '.') }}
                        dari {{ number_format($rows->total(), 0, ',', '.') }} record.
                    </p>
                </div>
                @if($canFinancial)
                    <span class="badge text-bg-warning-subtle text-warning-emphasis align-self-md-start">Akses keuangan aktif</span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>UMKM</th>
                            <th>Wilayah</th>
                            <th>Klasifikasi</th>
                            <th class="text-end">Pekerja</th>
                            @if($canFinancial)
                                <th class="text-end">Modal</th>
                                <th class="text-end">Penjualan</th>
                            @endif
                            <th>Mutu</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $qualityText = $label($row->quality_status);
                                $qualityClass = $row->quality_status === null
                                    ? 'text-bg-secondary'
                                    : (\Illuminate\Support\Str::contains((string)$row->quality_status, ['lengkap', 'terpetakan'])
                                        ? 'text-bg-success-subtle text-success-emphasis'
                                        : 'text-bg-warning-subtle text-warning-emphasis');
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $row->business_name }}</div>
                                    <div class="small text-body-secondary">{{ $row->umkm_code }}</div>
                                </td>
                                <td>
                                    <div>{{ $row->district_name ?: 'Belum terasosiasi' }}</div>
                                    <div class="small text-body-secondary">{{ $row->village_name ?: 'Kelurahan belum tersedia' }}</div>
                                </td>
                                <td>
                                    <div>{{ $row->category_name ?: 'Belum tersedia' }}</div>
                                    <div class="small text-body-secondary">{{ $row->type_name ?: 'Jenis belum tersedia' }}</div>
                                </td>
                                <td class="text-end">{{ $row->employee_count === null ? 'Belum tersedia' : number_format((int)$row->employee_count, 0, ',', '.') }}</td>
                                @if($canFinancial)
                                    <td class="text-end">{{ $money($row->capital_amount) }}</td>
                                    <td class="text-end">{{ $money($row->annual_sales_amount) }}</td>
                                @endif
                                <td>
                                    <span class="badge {{ $qualityClass }}">{{ $qualityText }}</span>
                                    <div class="small text-body-secondary mt-1">{{ number_format((int)$row->quality_flag_count, 0, ',', '.') }} flag terbuka</div>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin-dinas.umkm.show', $row->id) }}">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canFinancial ? 8 : 6 }}" class="text-body-secondary">Tidak ada data pada filter aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-top pt-3 mt-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div class="small text-body-secondary">
                        Halaman {{ number_format($currentPage, 0, ',', '.') }} dari {{ number_format($lastPage, 0, ',', '.') }}
                    </div>

                    @if($lastPage > 1)
                        <nav aria-label="Navigasi Halaman Data UMKM">
                            <ul class="pagination pagination-sm mb-0 flex-wrap">
                                <li class="page-item @if($rows->onFirstPage()) disabled @endif">
                                    <a class="page-link" href="{{ $rows->previousPageUrl() ?: '#' }}">Sebelumnya</a>
                                </li>

                                @php $previousRendered = null; @endphp
                                @foreach($pageNumbers as $page)
                                    @if($previousRendered !== null && $page - $previousRendered > 1)
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    @endif

                                    <li class="page-item @if($page === $currentPage) active @endif">
                                        <a class="page-link" href="{{ $rows->url($page) }}">{{ number_format($page, 0, ',', '.') }}</a>
                                    </li>

                                    @php $previousRendered = $page; @endphp
                                @endforeach

                                <li class="page-item @if(!$rows->hasMorePages()) disabled @endif">
                                    <a class="page-link" href="{{ $rows->nextPageUrl() ?: '#' }}">Berikutnya</a>
                                </li>
                            </ul>
                        </nav>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
