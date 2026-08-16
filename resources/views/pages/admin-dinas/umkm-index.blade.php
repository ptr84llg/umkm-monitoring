@extends('layouts.dashboard')
@section('title', 'Data UMKM Internal')
@section('content')
@php
    $rows = $data['rows'];
    $filters = $data['filters'] ?? [];
    $options = $data['filter_options'] ?? [];
    $canFinancial = (bool)($data['can_view_financial'] ?? false);
    $label = static fn ($value): string => $value ? \Illuminate\Support\Str::headline((string)$value) : 'Belum tersedia';
    $money = static fn ($value): string => ($value === null || $value === '') ? 'Belum tersedia' : 'Rp '.number_format((float)$value, 0, ',', '.');
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border-0 shadow-sm">
        <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-2">Read-only Internal</span>
                <h1 class="h3 mb-2">Data UMKM Internal</h1>
                <p class="text-body-secondary mb-0">Penelusuran record tanpa mengubah nilai sumber. Gunakan filter untuk drill-down.</p>
            </div>
            <a href="{{ route('admin-dinas.analytics.index') }}" class="btn btn-primary align-self-lg-start">Buka Analitik</a>
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
                        @foreach([25,50,100] as $size)
                            <option value="{{ $size }}" @selected((int)($filters['per_page'] ?? 25) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin-dinas.umkm.index') }}" class="btn btn-outline-secondary">Reset</a>
                    <button class="btn btn-primary" type="submit">Terapkan</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Daftar UMKM</h2>
                    <p class="text-body-secondary mb-0">{{ number_format($rows->total(), 0, ',', '.') }} record pada konteks aktif.</p>
                </div>
                @if($canFinancial)<span class="badge text-bg-warning-subtle text-warning-emphasis align-self-start">Akses keuangan aktif</span>@endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>UMKM</th><th>Wilayah</th><th>Klasifikasi</th><th class="text-end">Pekerja</th>
                            @if($canFinancial)<th class="text-end">Modal</th><th class="text-end">Penjualan</th>@endif
                            <th>Mutu</th><th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td><div class="fw-semibold">{{ $row->business_name }}</div><div class="small text-body-secondary">{{ $row->umkm_code }}</div></td>
                                <td><div>{{ $row->district_name ?: 'Belum terasosiasi' }}</div><div class="small text-body-secondary">{{ $row->village_name ?: 'Kelurahan belum tersedia' }}</div></td>
                                <td><div>{{ $row->category_name ?: 'Belum tersedia' }}</div><div class="small text-body-secondary">{{ $row->type_name ?: 'Jenis belum tersedia' }}</div></td>
                                <td class="text-end">{{ $row->employee_count === null ? 'Belum tersedia' : number_format((int)$row->employee_count, 0, ',', '.') }}</td>
                                @if($canFinancial)<td class="text-end">{{ $money($row->capital_amount) }}</td><td class="text-end">{{ $money($row->annual_sales_amount) }}</td>@endif
                                <td><div>{{ $label($row->quality_status) }}</div><div class="small text-body-secondary">{{ (int)$row->quality_flag_count }} flag terbuka</div></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin-dinas.umkm.show', $row->id) }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canFinancial ? 8 : 6 }}" class="text-body-secondary">Tidak ada data pada filter aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $rows->links() }}</div>
        </div>
    </section>
</div>
@endsection
