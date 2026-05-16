@extends('layouts.expert-validation')
@section('title','Daftar Instrumen Validator')
@section('content')<x-umkm.table-card><table class="table"><tbody>@foreach($items as $i)<tr><td>{{ $i->code }}</td><td>{{ $i->title }}</td><td><a href="{{ route('expert.validator.open',$i) }}">Buka Artefak</a></td></tr>@endforeach</tbody></table></x-umkm.table-card>@endsection
