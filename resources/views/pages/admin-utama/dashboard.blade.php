@extends('layouts.dashboard')
@section('title','Dashboard Admin Utama')
@section('content')<x-umkm.card><x-umkm.summary-card title="Akun" :value="$data['users']"/><x-umkm.summary-card title="Role" :value="$data['roles']"/><x-umkm.summary-card title="Permission" :value="$data['permissions']"/><x-umkm.summary-card title="Security Event" :value="$data['security_events']"/></x-umkm.card>@endsection
