@extends('layouts.dashboard')
@section('title','Daftar Usulan Perubahan')
@section('content')<x-umkm.table-card><table class="table"><thead><tr><th>ID</th><th>UMKM</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@foreach($submissions as $s)<tr><td>{{ $s->id }}</td><td>{{ $s->umkm_id }}</td><td>{{ $s->status_data }}</td><td><a href="{{ route('admin-dinas.proposals.show',$s) }}">Detail</a></td></tr>@endforeach</tbody></table></x-umkm.table-card>@endsection
