@extends('layouts.print-export')
@section('title', 'Ekspor Laporan')
@section('content')
    <x-umkm.data-display.card>
        <form method="POST" action="{{ route('export.generate') }}">
            @csrf
            <x-umkm.forms.form-select name="report_type" label="Jenis Laporan">
                <option value="umkm_ringkas">UMKM Ringkas</option>
                <option value="klasifikasi_usaha">Klasifikasi Usaha Lokal</option>
                <option value="wilayah">Wilayah</option>
                <option value="legalitas_status">Status Legalitas</option>
                <option value="kinerja_periodik">Kinerja Periodik</option>
                <option value="survei">Survei</option>
                <option value="validasi_ahli">Validasi Ahli</option>
                <option value="all">Semua Ringkasan</option>
            </x-umkm.forms.form-select>
            <x-umkm.forms.form-select name="format" label="Format">
                <option value="json">JSON</option>
                <option value="csv">CSV</option>
            </x-umkm.forms.form-select>
            <x-umkm.forms.form-textarea name="reason" label="Alasan Ekspor" />
            <button class="btn btn-primary">Ekspor</button>
        </form>
    </x-umkm.data-display.card>
@endsection
