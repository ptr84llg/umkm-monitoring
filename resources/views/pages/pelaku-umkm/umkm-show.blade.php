@extends('layouts.dashboard')

@section('title', 'Detail Usaha Saya')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-muted mb-1">Data usaha terikat</p>
            <h1 class="h3 mb-2">{{ $umkm->business_name }}</h1>
            <p class="mb-0">Profil ditampilkan apa adanya dari data sistem. Checkpoint 10C tidak melakukan perubahan terhadap data sumber.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('pelaku-umkm.umkm.index') }}">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Kode UMKM</dt>
                <dd class="col-sm-8">{{ $umkm->umkm_code }}</dd>

                <dt class="col-sm-4">Nama Usaha</dt>
                <dd class="col-sm-8">{{ $umkm->business_name }}</dd>

                <dt class="col-sm-4">Status Data</dt>
                <dd class="col-sm-8">{{ $umkm->status_data ?? '-' }}</dd>

                <dt class="col-sm-4">Mutu Data</dt>
                <dd class="col-sm-8">{{ $umkm->quality_status ?? '-' }}</dd>

                <dt class="col-sm-4">Tanggal Berdiri</dt>
                <dd class="col-sm-8">{{ $umkm->established_date?->format('d-m-Y') ?? '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="alert alert-info mt-3 mb-0">
        Mekanisme usulan perubahan profil yang tidak mengubah data sumber akan dibangun pada Checkpoint 10D.
    </div>
</div>
@endsection