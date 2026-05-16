@extends('layouts.dashboard')
@section('title','Profil Usaha Saya')
@section('content')<x-umkm.table-card><table class="table"><thead><tr><th>Kode</th><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@foreach($umkms as $u)<tr><td>{{ $u->umkm_code }}</td><td>{{ $u->business_name }}</td><td>{{ $u->status_data }}</td><td><a href="{{ route('pelaku-umkm.profiles.show',$u) }}">Detail</a></td></tr>@endforeach</tbody></table></x-umkm.table-card>@endsection
