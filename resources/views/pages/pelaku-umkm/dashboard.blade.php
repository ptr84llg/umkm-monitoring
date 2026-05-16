@extends('layouts.dashboard')
@section('title','Dashboard Pelaku UMKM')
@section('content')<x-umkm.card>@foreach($data as $k=>$v)<x-umkm.summary-card :title="$k" :value="$v"/>@endforeach</x-umkm.card>@endsection
