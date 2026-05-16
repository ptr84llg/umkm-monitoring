@extends('layouts.dashboard')
@section('title','Smoke Test UI')
@section('content')<x-umkm.card><x-umkm.summary-card title="Total UMKM" value="0"/><x-umkm.badge type="info">Draft</x-umkm.badge><x-umkm.empty-state message="Belum ada data."/></x-umkm.card>@endsection
