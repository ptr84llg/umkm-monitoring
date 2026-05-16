@extends('layouts.dashboard')
@section('title','Konten & Pengumuman')
@section('content')<x-umkm.card><form method="POST" action="{{ route('admin-utama.announcements.store') }}">@csrf <x-umkm.form-input name="title" label="Judul"/><x-umkm.form-textarea name="content" label="Konten Naratif (WYSIWYG terbatas)"/><button class="btn btn-primary">Simpan</button></form></x-umkm.card>@endsection
