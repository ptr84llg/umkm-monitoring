@extends('layouts.dashboard')
@section('title','Rekap Validasi Ahli')
@section('content')<x-umkm.table-card><table class="table"><tbody>@foreach($data as $r)<tr><td>{{ $r['instrument_id'] }}</td><td>{{ $r['status'] }}</td><td>{{ $r['total'] }}</td><td>{{ $r['avg_score'] }}</td></tr>@endforeach</tbody></table></x-umkm.table-card>@endsection
