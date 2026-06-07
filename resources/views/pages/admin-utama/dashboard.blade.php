@extends('layouts.dashboard')

@section('title', 'Dashboard Admin Utama')

@section('content')
    <section class="umkm-card card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <span class="badge rounded-pill text-bg-light border mb-3">Admin Utama · Super Admin</span>
                    <h1 class="h3 mb-3">Pusat Kendali Sistem Monitoring UMKM</h1>
                    <p class="text-muted mb-0">
                        Dashboard ini menjadi shell awal untuk memantau sistem, keamanan, konfigurasi, kualitas data,
                        pengguna, dan kesiapan modul. Pada tahap ini menu masih dikunci sebagai skeleton agar tidak
                        membuka CRUD atau akses operasional sebelum guard, permission, dan audit siap.
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-4 p-3 bg-light-subtle">
                        <div class="small text-muted mb-1">Status tahap</div>
                        <div class="fw-semibold">AdminUtama-1B · Dashboard Shell</div>
                        <div class="small text-muted mt-2">
                            Theme switching 7 opsi sudah terdeteksi, tetapi belum diaktifkan pada tahap ini.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <x-umkm.data-display.summary-card title="Akun" :value="$data['users'] ?? 0" />
        </div>
        <div class="col-md-6 col-xl-3">
            <x-umkm.data-display.summary-card title="Role" :value="$data['roles'] ?? 0" />
        </div>
        <div class="col-md-6 col-xl-3">
            <x-umkm.data-display.summary-card title="Permission" :value="$data['permissions'] ?? 0" />
        </div>
        <div class="col-md-6 col-xl-3">
            <x-umkm.data-display.summary-card title="Security Event" :value="$data['security_events'] ?? 0" />
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <section class="umkm-card card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
                        <div>
                            <h2 class="h5 mb-1">Menu Skeleton Admin Utama</h2>
                            <p class="text-muted small mb-0">
                                Struktur menu mengikuti ruang kerja Admin Utama: akses, referensi, governance, publikasi, dan validasi.
                            </p>
                        </div>
                        <span class="badge rounded-pill text-bg-warning">Belum CRUD</span>
                    </div>

                    <div class="row g-3">
                        @foreach ($menuGroups as $menu)
                            @php
                                $isActive = ($menu['status'] ?? '') === 'Aktif';
                                $routeName = $menu['route_name'] ?? null;
                                $href = ($isActive && $routeName && Route::has($routeName)) ? route($routeName) : '#';
                            @endphp

                            <div class="col-md-6">
                                <div class="border rounded-4 p-3 h-100 bg-white">
                                    <div class="d-flex justify-content-between gap-3 mb-2">
                                        <div>
                                            <h3 class="h6 mb-1">{{ $menu['title'] }}</h3>
                                            <span class="small text-muted">{{ $menu['permission'] }}</span>
                                        </div>
                                        <span class="badge rounded-pill {{ $isActive ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $menu['status'] }}
                                        </span>
                                    </div>

                                    <p class="small text-muted mb-3">{{ $menu['description'] }}</p>

                                    @if ($isActive)
                                        <a href="{{ $href }}" class="btn btn-sm btn-outline-primary">
                                            Buka modul aktif
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                            Menunggu batch lanjutan
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="umkm-card card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Snapshot Sistem</h2>

                    <div class="vstack gap-3">
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">API log</span>
                            <strong>{{ number_format($systemSnapshot['api_logs'] ?? 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Audit log</span>
                            <strong>{{ number_format($systemSnapshot['audit_logs'] ?? 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Kategori usaha</span>
                            <strong>{{ number_format($systemSnapshot['business_category_references'] ?? 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Jenis usaha</span>
                            <strong>{{ number_format($systemSnapshot['business_type_references'] ?? 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Referensi wilayah</span>
                            <strong>{{ number_format($systemSnapshot['regions'] ?? 0) }}</strong>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <section class="umkm-card card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
                        <div>
                            <h2 class="h5 mb-1">Kesiapan 7 Theme</h2>
                            <p class="text-muted small mb-0">
                                Theme berikut sudah tersedia sebagai CSS. Penggantian theme akan dibuat pada batch terpisah
                                dengan permission dan audit.
                            </p>
                        </div>
                        <span class="badge rounded-pill text-bg-light border">7 opsi</span>
                    </div>

                    <div class="row g-2">
                        @foreach ($themeOptions as $theme)
                            <div class="col-sm-6 col-lg-4">
                                <div class="border rounded-4 p-3 h-100">
                                    <div class="fw-semibold">{{ $theme['label'] }}</div>
                                    <div class="small text-muted">{{ $theme['key'] }}</div>
                                    <div class="small text-muted mt-2">{{ $theme['file'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="umkm-card card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Catatan Guard</h2>

                    <div class="vstack gap-2">
                        @foreach ($governanceNotes as $note)
                            <div class="border rounded-4 p-3 bg-light-subtle">
                                <span class="small">{{ $note }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
