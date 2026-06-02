@extends('layouts.dashboard')
@section('title','Smoke Test UI')
@section('content')<x-umkm.data-display.card><x-umkm.data-display.summary-card title="Total UMKM" value="0"/><x-umkm.data-display.badge type="info">Draft</x-umkm.data-display.badge><x-umkm.feedback.empty-state message="Belum ada data."/></x-umkm.data-display.card>@endsection
