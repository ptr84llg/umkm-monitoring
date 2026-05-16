@extends('layouts.dashboard')
@section('title','Rekap Survei')
@section('content')<x-umkm.table-card><table class="table"><thead><tr><th>Instrumen</th><th>Status</th><th>Total</th></tr></thead><tbody>@foreach($data as $r)<tr><td>{{ $r->survey_instrument_id }}</td><td>{{ $r->status }}</td><td>{{ $r->total }}</td></tr>@endforeach</tbody></table></x-umkm.table-card>@endsection
