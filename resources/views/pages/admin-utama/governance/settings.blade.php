@extends('layouts.dashboard')

@section('title', 'Admin Utama - Pengaturan Sistem')
@section('page_title', 'Pengaturan Sistem')

@section('content')
    <div class="umkm-theme-management vstack gap-4">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm" role="alert">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm" role="alert">
                <strong>Perubahan belum dapat disimpan.</strong>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <section class="card border-0 shadow-sm umkm-theme-hero">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-xl-8">
                        <span class="d-inline-flex align-items-center badge rounded-pill text-bg-light border mb-2 umkm-theme-kicker">Tata Kelola Tampilan</span>
                        <h2 class="h4 fw-bold mb-2">Manajemen Theme Sistem</h2>
                        <p class="text-muted mb-0">Pilih salah satu theme untuk melihat preview langsung. Setelah pilihan dibuat, sistem akan menampilkan konfirmasi sebelum theme disimpan ke backend.</p>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="card border-0 shadow-sm h-100 umkm-theme-active-badge">
                            <div class="card-body p-3">
                                <span class="d-block small text-muted text-uppercase fw-bold">Theme Aktif</span>
                                <strong class="d-block fs-5 mt-1" data-theme-active-label>{{ $activeThemeLabel ?? 'Green' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <x-umkm.data-display.table-card>
            <form data-theme-manager data-theme-endpoint="{{ route('admin-utama.governance.settings.theme') }}" data-active-theme="{{ $activeThemeKey }}" data-active-label="{{ $activeThemeLabel }}" autocomplete="off">
                @csrf
                <div class="alert border-0 shadow-sm d-none" role="alert" data-theme-feedback></div>

                <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3" role="radiogroup" aria-label="Pilihan theme sistem">
                    @foreach ($themeOptions as $theme)
                        <div class="col" data-theme-option="{{ $theme['key'] }}">
                            <div class="form-check p-0 h-100">
                                <input class="btn-check umkm-theme-radio" type="radio" name="theme_key" id="theme-{{ $theme['key'] }}" value="{{ $theme['key'] }}" data-theme-label="{{ $theme['label'] }}" @checked($theme['active'])>
                                <label class="card h-100 border-0 shadow-sm umkm-theme-card umkm-theme-preview--{{ $theme['key'] }} {{ $theme['active'] ? 'is-active' : '' }}" for="theme-{{ $theme['key'] }}">
                                    <span class="umkm-theme-swatch ratio ratio-21x9" aria-hidden="true">
                                        <span class="umkm-theme-swatch-row"><span class="umkm-theme-swatch-pill"></span><span class="umkm-theme-swatch-pill"></span><span class="umkm-theme-swatch-pill"></span></span>
                                        <span class="umkm-theme-swatch-row"><span class="umkm-theme-swatch-pill"></span><span class="umkm-theme-swatch-pill"></span></span>
                                    </span>
                                    <span class="card-body d-flex flex-column gap-3">
                                        <span class="d-block"><strong class="d-block">{{ $theme['label'] }}</strong><span class="d-block text-muted small mt-2">{{ $theme['description'] }}</span></span>
                                        <span class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-auto"><span class="badge rounded-pill text-bg-light border umkm-theme-tone">{{ $theme['tone'] }}</span><span class="badge rounded-pill text-bg-success umkm-theme-status {{ $theme['active'] ? '' : 'd-none' }}" data-theme-status>Aktif</span></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="alert alert-light border shadow-sm mt-4 mb-0 umkm-theme-security-note" role="note">
                    <strong>Catatan keamanan</strong>
                    <span>Preview terjadi di browser, tetapi penyimpanan tetap melalui request internal yang membawa CSRF, header internal, validasi role/permission, allowlist backend, dan audit log.</span>
                </div>
            </form>
        </x-umkm.data-display.table-card>

        <div class="modal fade" id="themeConfirmModal" tabindex="-1" aria-labelledby="themeConfirmModalLabel" aria-hidden="true" data-theme-confirm-modal>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content umkm-theme-confirm-modal">
                    <div class="modal-header">
                        <div><span class="d-inline-flex badge rounded-pill text-bg-light border mb-2 umkm-theme-preview-kicker">Konfirmasi Theme</span><h5 class="modal-title" id="themeConfirmModalLabel">Gunakan theme ini?</h5></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup" data-theme-cancel></button>
                    </div>
                    <div class="modal-body"><p class="mb-0">Theme <strong data-theme-pending-label>terpilih</strong> sudah ditampilkan sebagai preview. Pilih <strong>Gunakan Theme</strong> untuk menyimpan perubahan ke sistem.</p></div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-theme-cancel>Batalkan</button><button type="button" class="btn btn-primary" data-theme-confirm-save>Gunakan Theme</button></div>
                </div>
            </div>
        </div>
    </div>
@endsection