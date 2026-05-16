@extends('layouts.dashboard')
@section('title','Dashboard Admin Dinas')
@section('content')<x-umkm.card><x-umkm.summary-card title="UMKM Resmi" :value="$data['official_umkm']"/><x-umkm.summary-card title="Perlu Perbaikan" :value="$data['need_fix']"/><x-umkm.summary-card title="Diajukan" :value="$data['pending']"/></x-umkm.card>@endsection
