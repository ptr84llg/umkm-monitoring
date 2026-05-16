@extends('layouts.dashboard')
@section('title','Detail Profil UMKM')
@section('content')<x-umkm.card><h2>{{ $profile['umkm']->business_name }}</h2><p>Status kualitas: {{ $profile['quality_status'] }}</p><p>Kontak owner: {{ $masked['owner_phone'] ?? '***' }} / {{ $masked['owner_email'] ?? '***' }}</p><p>NIB: {{ $masked['nib_number'] ?? '***' }}</p><p>Omzet: {{ $masked['monthly_revenue'] ?? '***' }}</p><a class="btn btn-outline-primary" href="{{ route('admin-dinas.umkm.edit',$profile['umkm']) }}">Edit</a></x-umkm.card>@endsection
