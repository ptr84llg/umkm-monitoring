@extends('layouts.dashboard')
@section('title','Tambah UMKM Resmi')
@section('content')<x-umkm.card><form method="POST" action="{{ route('admin-dinas.umkm.store') }}">@csrf <x-umkm.form-input name="umkm_code" label="Kode UMKM"/><x-umkm.form-input name="business_name" label="Nama Usaha"/><x-umkm.form-select name="status_data" label="Status Data"><option value="draft">Draft</option><option value="resmi">Resmi</option></x-umkm.form-select><x-umkm.form-input name="quality_status" label="Status Kualitas"/><button class="btn btn-primary">Simpan</button></form></x-umkm.card>@endsection
