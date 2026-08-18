@extends('layouts.dashboard')

@section('title', 'Dashboard Admin Utama')

@section('content')
    <section class="umkm-card card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <span class="badge rounded-pill text-bg-light border mb-3">Admin Utama</span>
                    <h1 class="h3 mb-3">Pusat Kendali Sistem Monitoring UMKM</h1>
                    <p class="text-muted mb-0">
                        Halaman ini digunakan untuk memantau keamanan, kualitas data, pengguna, pengaturan, dan layanan yang tersedia sesuai kewenangan.
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded-4 p-3 bg-light-subtle">
                        <div class="small text-muted mb-1">Status sistem</div>
                        <div class="fw-semibold">Pusat Kendali Sistem Aktif</div>
                        <div class="small text-muted mt-2">
                            Tersedia 7 pilihan tema tampilan.
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
            <x-umkm.data-display.summary-card title="Peran" :value="$data['roles'] ?? 0" />
        </div>
        <div class="col-md-6 col-xl-3">
            <x-umkm.data-display.summary-card title="Izin Akses" :value="$data['permissions'] ?? 0" />
        </div>
        <div class="col-md-6 col-xl-3">
            <x-umkm.data-display.summary-card title="Aktivitas Keamanan" :value="$data['security_events'] ?? 0" />
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <section class="umkm-card card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
                        <div>
                            <h2 class="h5 mb-1">Bagian Pengelolaan Admin Utama</h2>
                            <p class="text-muted small mb-0">
                                Menu mencakup pengelolaan akses, data referensi, tata kelola, publikasi, dan penilaian.
                            </p>
                        </div>
                        <span class="badge rounded-pill text-bg-warning">Belum Tersedia</span>
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
                                            <span class="small text-muted">Akses sesuai kewenangan</span>
                                        </div>
                                        <span class="badge rounded-pill {{ $isActive ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $menu['status'] }}
                                        </span>
                                    </div>

                                    <p class="small text-muted mb-3">{{ $menu['description'] }}</p>

                                    @if ($isActive)
                                        <a href="{{ $href }}" class="btn btn-sm btn-outline-primary">
                                            Buka
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                            Belum Tersedia
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
                    <h2 class="h5 mb-3">Ringkasan Sistem</h2>

                    <div class="vstack gap-3">
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Permintaan layanan</span>
                            <strong>{{ number_format($systemSnapshot['api_logs'] ?? 0) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Riwayat perubahan</span>
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
                            <h2 class="h5 mb-1">Pilihan Tema Tampilan</h2>
                            <p class="text-muted small mb-0">
                                Tema berikut tersedia sebagai pilihan tampilan sistem. Perubahan mengikuti kewenangan pengguna
                                dan dicatat dalam riwayat sistem.
                            </p>
                        </div>
                        <span class="badge rounded-pill text-bg-light border">7 opsi</span>
                    </div>

                    <div class="row g-2">
                        @foreach ($themeOptions as $theme)
                            <div class="col-sm-6 col-lg-4">
                                <div class="border rounded-4 p-3 h-100">
                                    <div class="fw-semibold">{{ $theme['label'] }}</div>
                                    <div class="small text-muted">Pilihan tampilan</div>
                                    <div class="small text-muted mt-2">Tampilan siap digunakan</div>
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
                    <h2 class="h5 mb-3">Catatan Keamanan</h2>

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
