@extends('layouts.dashboard')
@section('title','Detail Profil Usaha')
@section('content')<x-umkm.card><h2>{{ $umkm->business_name }}</h2><p>Kode: {{ $umkm->umkm_code }}</p><p>Status data: {{ $umkm->status_data }}</p><p>Status kualitas: {{ $umkm->quality_status ?? '-' }}</p><p>Ringkasan terbatas: data sensitif tidak ditampilkan penuh.</p><a class="btn btn-outline-primary" href="{{ route('proposals.create') }}">Ajukan Pembaruan Data</a></x-umkm.card>@endsection
