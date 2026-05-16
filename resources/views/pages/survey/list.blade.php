@extends('layouts.dashboard')
@section('title','Survei Aktif')
@section('content')<x-umkm.card><a class="btn btn-outline-primary" href="{{ route('survey.google.redirect') }}">Hubungkan Login Google</a><ul>@foreach($items as $s)<li>{{ $s->title }} - <a href="{{ route('survey.fill',$s) }}">Isi</a></li>@endforeach</ul></x-umkm.card>@endsection
