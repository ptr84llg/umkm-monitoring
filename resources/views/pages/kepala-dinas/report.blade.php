@extends('layouts.print-export')
@section('title','Laporan Ringkas Kepala Dinas')
@section('content')<x-umkm.data-display.card><h2 class="h5">Laporan Ringkas UMKM</h2><pre>{{ json_encode($summary['indicators'], JSON_PRETTY_PRINT) }}</pre><form method="POST" action="{{ route('kepala-dinas.export') }}">@csrf<x-umkm.forms.form-textarea name="reason" label="Alasan Mengunduh Laporan (wajib)"/><button class="btn btn-primary">Unduh Laporan</button></form></x-umkm.data-display.card>@endsection
