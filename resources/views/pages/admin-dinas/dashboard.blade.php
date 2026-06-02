@extends('layouts.dashboard')
@section('title','Dashboard Admin Dinas')
@section('content')<x-umkm.data-display.card><x-umkm.data-display.summary-card title="UMKM Resmi" :value="$data['official_umkm']"/><x-umkm.data-display.summary-card title="Perlu Perbaikan" :value="$data['need_fix']"/><x-umkm.data-display.summary-card title="Diajukan" :value="$data['pending']"/></x-umkm.data-display.card>@endsection
