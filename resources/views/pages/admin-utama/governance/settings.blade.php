@extends('layouts.dashboard')

@section('title', 'Admin Utama - Pengaturan Sistem')
@section('page_title', 'Pengaturan Sistem')

@section('content')
    <div class="umkm-theme-management">
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

        <section class="umkm-theme-hero">
            <div>
                <h2>Manajemen Theme Sistem</h2>
                <p>
                    Pilih salah satu theme untuk melihat preview langsung. Setelah pilihan dibuat, sistem akan
                    menampilkan konfirmasi sebelum theme disimpan ke backend.
                </p>
            </div>

            <div class="umkm-theme-active-badge">
                <small>Theme Aktif</small>
                <strong data-theme-active-label>{{ $activeThemeLabel ?? 'Green' }}</strong>
            </div>
        </section>

        <x-umkm.data-display.table-card>
            <form
                data-theme-manager
                data-theme-endpoint="{{ route('admin-utama.governance.settings.theme') }}"
                data-active-theme="{{ $activeThemeKey }}"
                data-active-label="{{ $activeThemeLabel }}"
                autocomplete="off"
            >
                @csrf

                <div class="alert border-0 shadow-sm d-none" role="alert" data-theme-feedback></div>

                <div class="umkm-theme-grid" role="radiogroup" aria-label="Pilihan theme sistem">
                    @foreach ($themeOptions as $theme)
                        <div class="umkm-theme-option" data-theme-option="{{ $theme['key'] }}">
                            <input
                                class="umkm-theme-radio"
                                type="radio"
                                name="theme_key"
                                id="theme-{{ $theme['key'] }}"
                                value="{{ $theme['key'] }}"
                                data-theme-label="{{ $theme['label'] }}"
                                @checked($theme['active'])
                            >

                            <label class="umkm-theme-card umkm-theme-preview--{{ $theme['key'] }} {{ $theme['active'] ? 'is-active' : '' }}" for="theme-{{ $theme['key'] }}">
                                <span class="umkm-theme-swatch" aria-hidden="true">
                                    <span class="umkm-theme-swatch-row">
                                        <span class="umkm-theme-swatch-pill"></span>
                                        <span class="umkm-theme-swatch-pill"></span>
                                        <span class="umkm-theme-swatch-pill"></span>
                                    </span>
                                    <span class="umkm-theme-swatch-row">
                                        <span class="umkm-theme-swatch-pill"></span>
                                        <span class="umkm-theme-swatch-pill"></span>
                                    </span>
                                </span>

                                <span class="umkm-theme-card-body">
                                    <strong>{{ $theme['label'] }}</strong>
                                    <p>{{ $theme['description'] }}</p>
                                </span>

                                <span class="umkm-theme-card-footer">
                                    <span class="umkm-theme-tone">{{ $theme['tone'] }}</span>
                                    <span class="umkm-theme-status {{ $theme['active'] ? '' : 'd-none' }}" data-theme-status>Aktif</span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="umkm-theme-security-note">
                    <strong>Catatan keamanan</strong>
                    <span>
                        Preview terjadi di browser, tetapi penyimpanan tetap melalui request internal yang membawa CSRF,
                        header internal, validasi role/permission, allowlist backend, dan audit log.
                    </span>
                </div>
            </form>
        </x-umkm.data-display.table-card>

        <div class="modal fade" id="themeConfirmModal" tabindex="-1" aria-labelledby="themeConfirmModalLabel" aria-hidden="true" data-theme-confirm-modal>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content umkm-theme-confirm-modal">
                    <div class="modal-header">
                        <div>
                            <span class="umkm-theme-preview-kicker">Konfirmasi Theme</span>
                            <h5 class="modal-title" id="themeConfirmModalLabel">Gunakan theme ini?</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup" data-theme-cancel></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            Theme <strong data-theme-pending-label>terpilih</strong> sudah ditampilkan sebagai preview.
                            Pilih <strong>Gunakan Theme</strong> untuk menyimpan perubahan ke sistem.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-theme-cancel>
                            Batalkan
                        </button>
                        <button type="button" class="btn btn-primary" data-theme-confirm-save>
                            Gunakan Theme
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection