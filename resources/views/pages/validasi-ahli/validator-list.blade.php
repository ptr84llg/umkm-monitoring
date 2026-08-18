@extends('layouts.expert-validation')
@section('title','Daftar Penilaian Ahli')
@section('content')<x-umkm.data-display.table-card><table class="table"><tbody>@foreach($items as $i)<tr><td>{{ $i->code }}</td><td>{{ $i->title }}</td><td><a href="{{ route('expert.validator.open',$i) }}">Buka Penilaian</a></td></tr>@endforeach</tbody></table></x-umkm.data-display.table-card>@endsection
