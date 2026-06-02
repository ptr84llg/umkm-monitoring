@extends('layouts.dashboard')
@section('title','Dashboard Pelaku UMKM')
@section('content')<x-umkm.data-display.card>@foreach($data as $k=>$v)<x-umkm.data-display.summary-card :title="$k" :value="$v"/>@endforeach</x-umkm.data-display.card>@endsection
