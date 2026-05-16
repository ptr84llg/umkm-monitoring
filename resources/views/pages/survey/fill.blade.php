@extends('layouts.dashboard')
@section('title','Isi Survei')
@section('content')<x-umkm.card><form method="POST" action="{{ route('survey.draft',$survey) }}">@csrf @foreach($survey->questions as $q)<x-umkm.form-textarea :name="'answers['.$q->id.']'" :label="$q->question_text"/>@endforeach<button class="btn btn-secondary">Simpan Draft</button></form><form method="POST" action="{{ route('survey.submit',$survey) }}">@csrf<input type="hidden" name="confirm_submit" value="1"><button class="btn btn-primary">Submit Final</button></form></x-umkm.card>@endsection
