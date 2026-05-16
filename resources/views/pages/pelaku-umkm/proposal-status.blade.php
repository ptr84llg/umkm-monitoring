@extends('layouts.dashboard')
@section('title','Status Usulan Saya')
@section('content')<x-umkm.table-card><table class="table"><thead><tr><th>ID</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>@foreach($submissions as $s)<tr><td>{{ $s->id }}</td><td>{{ $s->status_data }}</td><td>{{ $s->review_notes ?? '-' }}</td><td>@if($s->status_data==='perlu_perbaikan')<a href="{{ route('pelaku-umkm.proposals.fix',$s) }}">Perbaiki</a>@endif</td></tr>@endforeach</tbody></table></x-umkm.table-card>@endsection
