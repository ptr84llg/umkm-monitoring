@extends('layouts.dashboard')
@section('title','Konten & Pengumuman')
@section('content')<x-umkm.data-display.card><form method="POST" action="{{ route('admin-utama.announcements.store') }}">@csrf <x-umkm.forms.form-input name="title" label="Judul"/><x-umkm.forms.form-textarea name="content" label="Konten Naratif (WYSIWYG terbatas)"/><button class="btn btn-primary">Simpan</button></form></x-umkm.data-display.card>@endsection
