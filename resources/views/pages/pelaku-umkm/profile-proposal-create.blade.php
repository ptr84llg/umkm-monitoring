@extends('layouts.dashboard')

@section('title', 'Ajukan Perubahan Profil UMKM')

@section('content')
<div class="container-fluid py-3">
    <div class="mb-4">
        <p class="text-muted mb-1">Ajukan perubahan data usaha</p>
        <h1 class="h3">{{ $effectiveProfile['effective']['business_name'] ?? $umkm->business_name }}</h1>
        <p class="mb-0">Data awal tetap tersimpan. Perubahan yang Anda ajukan akan menjadi data saat ini setelah diperiksa dan disetujui Dinas.</p>
    </div>

    <form method="POST" action="{{ route('pelaku-umkm.profile-change.store', $umkm) }}" class="card">
        @csrf
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label" for="business_name">Nama Usaha</label>
                <input id="business_name" name="business_name" class="form-control" required maxlength="150" value="{{ old('business_name', $effectiveProfile['effective']['business_name'] ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="established_date">Tanggal Berdiri</label>
                <input id="established_date" name="established_date" type="date" class="form-control" value="{{ old('established_date', $effectiveProfile['effective']['established_date'] ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="employee_count">Jumlah Tenaga Kerja</label>
                <input id="employee_count" name="employee_count" type="number" min="0" class="form-control" value="{{ old('employee_count', $effectiveProfile['effective']['employee_count'] ?? '') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="marketing_method_id">Metode Pemasaran</label>
                <select id="marketing_method_id" name="marketing_method_id" class="form-select">
                    <option value="">Belum/ tidak terdata</option>
                    @foreach($marketingMethods as $method)
                        <option value="{{ $method->id }}" @selected((string) old('marketing_method_id', $effectiveProfile['effective']['marketing_method_id'] ?? '') === (string) $method->id)>{{ $method->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="alert alert-warning">
                Status administrasi, catatan kualitas data, sumber data, dan informasi sistem tidak dapat diubah melalui formulir ini.
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary" type="submit">Ajukan Perubahan</button>
            <a class="btn btn-outline-secondary" href="{{ route('pelaku-umkm.umkm.show', $umkm) }}">Batal</a>
        </div>
    </form>
</div>
@endsection