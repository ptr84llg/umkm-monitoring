@extends('layouts.dashboard')
@section('title', 'Analitik Keuangan Internal')
@section('content')
@php
    $filters = $data['filters'] ?? []; $options = $data['filter_options'] ?? []; $coverage = $data['financial']['coverage'] ?? []; $analysis = $data['loan_source_analysis'] ?? [];
    $money = static fn ($value): string => ($value === null || $value === '') ? 'Belum tersedia' : 'Rp '.number_format((float)$value,0,',','.');
    $label = static fn ($value): string => $value ? \Illuminate\Support\Str::headline((string)$value) : 'Belum tersedia';
    $pct = static fn ($filled,$total): string => $total > 0 ? number_format(($filled/$total)*100,1,',','.').'%' : '0,0%';
    $maxIdentified = max(1, collect($analysis['identified'] ?? [])->max('total_umkm') ?? 1); $maxIssue = max(1, collect($analysis['quality_issues'] ?? [])->max('total_umkm') ?? 1);
@endphp

<div class="d-flex flex-column gap-4">
    <section class="card border-0 shadow-sm"><div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div><span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis mb-2">Internal · Data Sensitif</span><h1 class="h3 mb-2">Analitik Ekonomi & Keuangan</h1><p class="text-body-secondary mb-0">Fokus pada cakupan data. Sistem tidak membuat total/rata-rata nominal sebagai indikator kebijakan dan tidak mengoreksi nilai sumber.</p></div>
        <a href="{{ route('admin-dinas.analytics.index') }}" class="btn btn-outline-primary align-self-lg-start">Analitik Umum</a>
    </div></section>

    <section class="card border-0 shadow-sm"><div class="card-body p-4"><form method="GET" action="{{ route('admin-dinas.analytics.financial') }}" class="row g-3">
        @foreach([['district_id','Kecamatan',$options['districts'] ?? []],['category_id','Kategori',$options['categories'] ?? []],['type_id','Jenis Usaha',$options['types'] ?? []],['marketing_method_id','Pemasaran',$options['marketingMethods'] ?? []]] as $filter)
        <div class="col-md-6 col-xl"><label class="form-label" for="{{ $filter[0] }}">{{ $filter[1] }}</label><select class="form-select" id="{{ $filter[0] }}" name="{{ $filter[0] }}"><option value="">Semua</option>@foreach($filter[2] as $item)<option value="{{ $item->id }}" @selected((string)($filters[$filter[0]] ?? '') === (string)$item->id)>{{ $item->name }}</option>@endforeach</select></div>
        @endforeach
        <div class="col-md-4 col-xl-auto d-flex align-items-end gap-2"><a href="{{ route('admin-dinas.analytics.financial') }}" class="btn btn-outline-secondary">Reset</a><button class="btn btn-warning">Terapkan</button></div>
    </form></div></section>

    <section class="row g-3">
        @foreach([['Modal','capital_filled'],['Penjualan Tahunan','annual_sales_filled'],['Omzet Bulanan','monthly_revenue_filled'],['Jumlah Pinjaman','loan_amount_filled'],['Sumber Pinjaman','loan_source_filled']] as $metric)
        @php $filled=(int)($coverage[$metric[1]] ?? 0); $total=(int)($coverage['total_umkm'] ?? 0); @endphp
        <div class="col-md-6 col-xl"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary">{{ $metric[0] }} terdata</div><div class="h2 mb-0">{{ number_format($filled,0,',','.') }}</div><div class="small text-body-secondary">{{ $pct($filled,$total) }}</div></div></div></div>
        @endforeach
    </section>

    <section class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="h5">Cakupan Keuangan per Kecamatan</h2><p class="text-body-secondary">Nilai 0 berbeda dari belum tersedia. Cakupan dihitung dari nilai non-NULL.</p>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Kecamatan</th><th class="text-end">UMKM</th><th class="text-end">Modal</th><th class="text-end">Penjualan</th><th class="text-end">Pinjaman</th><th class="text-end">Sumber</th></tr></thead><tbody>
        @forelse($data['financial']['districts'] ?? [] as $row)<tr><td>{{ $row['name'] }}</td><td class="text-end">{{ number_format($row['total_umkm'],0,',','.') }}</td><td class="text-end">{{ number_format($row['capital_filled'],0,',','.') }}</td><td class="text-end">{{ number_format($row['annual_sales_filled'],0,',','.') }}</td><td class="text-end">{{ number_format($row['loan_amount_filled'],0,',','.') }}</td><td class="text-end">{{ number_format($row['loan_source_filled'],0,',','.') }}</td></tr>@empty<tr><td colspan="6">Belum tersedia.</td></tr>@endforelse
        </tbody></table></div>
    </div></section>

    <section class="row g-4">
        <div class="col-xl-6"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><h2 class="h5">Sumber Pinjaman Teridentifikasi</h2><p class="text-body-secondary">Nilai sumber dipertahankan apa adanya.</p><div class="d-flex flex-column gap-3">
        @forelse($analysis['identified'] ?? [] as $row)<div><div class="d-flex justify-content-between"><span>{{ $row['raw_value'] }}</span><strong>{{ number_format($row['total_umkm'],0,',','.') }}</strong></div><progress class="w-100" max="{{ $maxIdentified }}" value="{{ $row['total_umkm'] }}"></progress></div>@empty<p class="text-body-secondary">Belum tersedia.</p>@endforelse
        </div><div class="small text-body-secondary mt-3">Belum tersedia: {{ number_format((int)($analysis['missing'] ?? 0),0,',','.') }} UMKM</div></div></div></div>
        <div class="col-xl-6"><div class="card border-warning-subtle shadow-sm h-100"><div class="card-body p-4"><h2 class="h5">Catatan Mutu Sumber Pinjaman</h2><p class="text-body-secondary">String yang memuat marker “data keuangan tidak tersedia” dipisahkan, bukan dinormalisasi menjadi kategori lain.</p><div class="d-flex flex-column gap-3">
        @forelse($analysis['quality_issues'] ?? [] as $row)<div><div class="d-flex justify-content-between"><span>{{ $row['raw_value'] }}</span><strong>{{ number_format($row['total_umkm'],0,',','.') }}</strong></div><progress class="w-100" max="{{ $maxIssue }}" value="{{ $row['total_umkm'] }}"></progress></div>@empty<p class="text-body-secondary">Tidak ada marker tersebut pada konteks aktif.</p>@endforelse
        </div></div></div></div>
    </section>

    <section class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="h5">Preview Nilai Keuangan Terdata</h2><p class="text-body-secondary">Maksimal 30 record dari dashboard service. Nilai kecil atau tidak lazim tetap ditampilkan tanpa koreksi.</p><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>UMKM</th><th>Kecamatan</th><th class="text-end">Modal</th><th class="text-end">Penjualan</th><th class="text-end">Pinjaman</th><th>Sumber</th><th>Mutu</th><th></th></tr></thead><tbody>
    @forelse($data['financial']['details'] ?? [] as $row)<tr><td>{{ $row['business_name'] }}<div class="small text-body-secondary">{{ $row['umkm_code'] }}</div></td><td>{{ $row['district_name'] ?? 'Belum terasosiasi' }}</td><td class="text-end">{{ $money($row['capital_amount']) }}</td><td class="text-end">{{ $money($row['annual_sales_amount']) }}</td><td class="text-end">{{ $money($row['loan_amount']) }}</td><td>{{ ($row['loan_source'] ?? '') !== '' ? $row['loan_source'] : 'Belum tersedia' }}</td><td>{{ $label($row['quality_status']) }}</td><td><a href="{{ route('admin-dinas.umkm.show',$row['id']) }}">Detail</a></td></tr>@empty<tr><td colspan="8">Tidak ada data.</td></tr>@endforelse
    </tbody></table></div></div></section>
</div>
@endsection
