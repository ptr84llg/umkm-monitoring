@extends('layouts.dashboard')
@section('title', 'Referensi Klasifikasi Usaha')
@section('content')
    <x-umkm.data-display.table-card>
        <h2 class="h5 mb-3">Kategori Usaha Lokal</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Slug</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->slug }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2 class="h5 mt-4 mb-3">Jenis Usaha Lokal</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Slug</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($types as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->slug }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-umkm.data-display.table-card>
@endsection
