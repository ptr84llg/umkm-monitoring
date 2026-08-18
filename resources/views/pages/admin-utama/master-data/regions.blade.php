@extends('layouts.dashboard')
@section('title', 'Admin Utama - Wilayah')
@section('content')
    <x-umkm.data-display.table-card>
        <h2 class="h5 text-capitalize">Daftar Wilayah</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Tingkat</th>
                    <th>Wilayah Induk</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($regions as $region)
                    <tr>
                        <td>{{ $region->code }}</td>
                        <td>{{ $region->name }}</td>
                        <td>{{ $region->level }}</td>
                        <td>{{ $region->parent_code ?? '-' }}</td>
                        <td>{{ $region->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-umkm.data-display.table-card>
@endsection
