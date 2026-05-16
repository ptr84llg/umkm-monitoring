@extends('layouts.print-export')
@section('title','Laporan Ringkas Kepala Dinas')
@section('content')<x-umkm.card><h2 class="h5">Laporan Ringkas Agregat</h2><pre>{{ json_encode($summary['indicators'], JSON_PRETTY_PRINT) }}</pre><form method="POST" action="{{ route('kepala-dinas.export') }}">@csrf<x-umkm.form-textarea name="reason" label="Alasan Ekspor (wajib)"/><button class="btn btn-primary">Minta Ekspor</button></form></x-umkm.card>@endsection
