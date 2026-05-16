@extends('layouts.dashboard')
@section('title','Dashboard Strategis Kepala Dinas')
@section('content')<x-umkm.security-alert>Privacy guard aktif: data hanya ringkasan agregat.</x-umkm.security-alert><x-umkm.card>@foreach($indicators as $k=>$v)<x-umkm.summary-card :title="$k" :value="$v"/>@endforeach</x-umkm.card><x-umkm.table-card><table class="table"><thead><tr><th>Status</th><th>Jumlah</th></tr></thead><tbody>@foreach($mapAggregate as $status=>$total)<tr><td>{{ $status }}</td><td>{{ $total }}</td></tr>@endforeach</tbody></table></x-umkm.table-card>@endsection
