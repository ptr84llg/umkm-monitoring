@extends('layouts.dashboard')
@section('title','Referensi KBLI')
@section('content')<x-umkm.table-card><table class="table"><thead><tr><th>Kode</th><th>Judul</th></tr></thead><tbody>@foreach($kbli as $row)<tr><td>{{ $row->kbli_code }}</td><td>{{ $row->title }}</td></tr>@endforeach</tbody></table></x-umkm.table-card>@endsection
