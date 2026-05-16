@extends('layouts.dashboard')
@section('title','Data UMKM Resmi')
@section('content')<x-umkm.table-card><a class="btn btn-primary" href="{{ route('admin-dinas.umkm.create') }}">Tambah UMKM Resmi</a><table class="table"><thead><tr><th>Kode</th><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@foreach($umkms as $u)<tr><td>{{ $u->umkm_code }}</td><td>{{ $u->business_name }}</td><td>{{ $u->status_data }}</td><td><a href="{{ route('admin-dinas.umkm.show',$u) }}">Detail</a></td></tr>@endforeach</tbody></table></x-umkm.table-card>@endsection
