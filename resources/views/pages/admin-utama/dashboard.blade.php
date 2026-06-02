@extends('layouts.dashboard')
@section('title','Dashboard Admin Utama')
@section('content')<x-umkm.data-display.card><x-umkm.data-display.summary-card title="Akun" :value="$data['users']"/><x-umkm.data-display.summary-card title="Role" :value="$data['roles']"/><x-umkm.data-display.summary-card title="Permission" :value="$data['permissions']"/><x-umkm.data-display.summary-card title="Security Event" :value="$data['security_events']"/></x-umkm.data-display.card>@endsection
