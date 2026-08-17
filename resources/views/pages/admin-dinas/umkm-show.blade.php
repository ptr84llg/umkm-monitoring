@extends('layouts.dashboard')
@section('title', 'Detail UMKM Internal')
@section('content')
@php
    $u = $data['umkm']; $s = $data['source']; $c = $data['classification']; $l = $data['location']; $b = $data['baseline']; $f = $data['financial']; $o = $data['owner'];
    $label = static fn ($value): string => $value ? \Illuminate\Support\Str::headline((string)$value) : 'Belum tersedia';
    $display = static fn ($value): string => ($value === null || $value === '') ? 'Belum tersedia' : (string)$value;
    $money = static fn ($value): string => ($value === null || $value === '') ? 'Belum tersedia' : 'Rp '.number_format((float)$value, 0, ',', '.');
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border shadow-sm"><div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div><span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-2">Detail Read-only</span><h1 class="h3 mb-1">{{ $u['business_name'] }}</h1><div class="text-body-secondary">{{ $u['umkm_code'] }}</div></div>
        <div class="d-flex gap-2 align-self-lg-start"><a href="{{ route('admin-dinas.umkm.index') }}" class="btn btn-outline-secondary">Data UMKM</a><a href="{{ route('admin-dinas.analytics.index') }}" class="btn btn-primary">Analitik</a></div>
    </div></section>

    <section class="row g-4">
        <div class="col-xl-6"><div class="card border shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h5">Identitas & Klasifikasi</h2><dl class="row mb-0">
                <dt class="col-sm-5">Status Data</dt><dd class="col-sm-7">{{ $label($u['status_data']) }}</dd>
                <dt class="col-sm-5">Status Mutu</dt><dd class="col-sm-7">{{ $label($u['quality_status']) }}</dd>
                <dt class="col-sm-5">Kategori</dt><dd class="col-sm-7">{{ $display($c['category']) }}</dd>
                <dt class="col-sm-5">Jenis Usaha</dt><dd class="col-sm-7">{{ $display($c['type']) }}</dd>
                <dt class="col-sm-5">Tenaga Kerja</dt><dd class="col-sm-7">{{ $b['employee_count'] === null ? 'Belum tersedia' : number_format((int)$b['employee_count'], 0, ',', '.') }}</dd>
                <dt class="col-sm-5">Pemasaran</dt><dd class="col-sm-7">{{ $display($b['marketing_method']) }}</dd>
            </dl>
        </div></div></div>
        <div class="col-xl-6"><div class="card border shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h5">Wilayah & Provenance</h2><dl class="row mb-0">
                <dt class="col-sm-5">Kecamatan</dt><dd class="col-sm-7">{{ $display($l['district']) }}</dd>
                <dt class="col-sm-5">Kelurahan</dt><dd class="col-sm-7">{{ $display($l['village']) }}</dd>
                <dt class="col-sm-5">Alamat</dt><dd class="col-sm-7">{{ $display($l['address_detail']) }}</dd>
                <dt class="col-sm-5">Source System</dt><dd class="col-sm-7">{{ $display($s['system']) }}</dd>
                <dt class="col-sm-5">Source Record ID</dt><dd class="col-sm-7">{{ $display($s['record_id']) }}</dd>
                <dt class="col-sm-5">Terakhir Terlihat</dt><dd class="col-sm-7">{{ $display($s['last_seen_at']) }}</dd>
            </dl>
        </div></div></div>
    </section>

    @if($f !== null)
    <section class="card border shadow-sm"><div class="card-body p-4">
        <span class="badge text-bg-warning-subtle text-warning-emphasis mb-2">Nilai Sumber · Internal</span><h2 class="h5">Informasi Keuangan</h2>
        <p class="text-body-secondary">Nilai ditampilkan sesuai sumber. Sistem tidak memperbaiki satuan atau nominal berdasarkan asumsi.</p>
        <div class="row g-3">
            @foreach([['Modal',$f['capital_amount']],['Penjualan Tahunan',$f['annual_sales_amount']],['Omzet Bulanan',$f['baseline_monthly_revenue']],['Pinjaman',$f['loan_amount']]] as $item)
                <div class="col-md-6 col-xl-3"><div class="border rounded-3 p-3 h-100"><div class="small text-body-secondary">{{ $item[0] }}</div><div class="h5 mb-0">{{ $money($item[1]) }}</div></div></div>
            @endforeach
        </div><div class="mt-3"><strong>Sumber Pinjaman:</strong> {{ $display($f['loan_source']) }}</div>
    </div></section>
    @endif

    <section class="row g-4">
        <div class="col-xl-6"><div class="card border shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h5">Legalitas</h2><p class="text-body-secondary">“Teridentifikasi” bukan validasi legal formal.</p>
            @forelse($data['legalities'] as $item)
                <div class="border rounded-3 p-3 mb-2"><strong>NIB:</strong> {{ $item['identified'] ? 'Teridentifikasi' : 'Belum teridentifikasi' }}
                    @if($data['legality_detail_visible'])<div>Nomor: {{ $display($item['nib_number']) }}</div><div>Risiko OSS: {{ $display($item['oss_risk_level']) }}</div>@endif
                </div>
            @empty<p class="text-body-secondary">Belum ada record legalitas.</p>@endforelse
        </div></div></div>
        <div class="col-xl-6"><div class="card border shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h5">Pemilik & Kontak</h2><dl class="row mb-0">
                <dt class="col-sm-5">Nama Pemilik</dt><dd class="col-sm-7">{{ $display($o['name']) }}</dd>
                <dt class="col-sm-5">Telepon</dt><dd class="col-sm-7">{{ $o['contact_visible'] ? $display($o['phone']) : 'Terbatas oleh izin akses' }}</dd>
                <dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $o['contact_visible'] ? $display($o['email']) : 'Terbatas oleh izin akses' }}</dd>
            </dl>
        </div></div></div>
    </section>

    <section class="card border shadow-sm"><div class="card-body p-4">
        <h2 class="h5">Catatan Mutu Data</h2><p class="text-body-secondary">Flag adalah hasil pemeriksaan mutu dan bukan koreksi otomatis.</p>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Kode</th><th>Kelompok</th><th>Severity</th><th>Deskripsi</th><th>Nilai</th><th>Status</th></tr></thead><tbody>
        @forelse($data['quality_flags'] as $flag)<tr><td>{{ $display($flag['code']) }}</td><td>{{ $label($flag['group']) }}</td><td>{{ $label($flag['severity']) }}</td><td>{{ $display($flag['description']) }}</td><td>{{ $display($flag['detected_value']) }}</td><td>{{ $label($flag['status']) }}</td></tr>
        @empty<tr><td colspan="6" class="text-body-secondary">Tidak ada flag mutu.</td></tr>@endforelse
        </tbody></table></div>
    </div></section>
</div>
@endsection
