@extends('layouts.dashboard')

@section('title', 'Admin Utama - Pengaturan Sistem')
@section('page_title', 'Pengaturan Sistem')

@section('content')
    <div class="umkm-theme-management">
        @if (session('status'))
            <div class="alert alert-success border-0 shadow-sm" role="alert">{{ session('status') }}</div>
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
                <p>Admin Utama dapat memilih satu theme aktif dari daftar resmi sistem. Theme diterapkan melalui <code>data-umkm-theme</code>, divalidasi backend, dan dicatat ke audit log saat terjadi perubahan.</p>
            </div>
            <div class="umkm-theme-active-badge"><small>Theme Aktif</small><strong>{{ $activeThemeLabel ?? 'Green' }}</strong></div>
        </section>

        <x-umkm.data-display.table-card>
            <form method="POST" action="{{ route('admin-utama.governance.settings.theme') }}">
                @csrf
                <div class="umkm-theme-grid">
                    @foreach ($themeOptions as $theme)
                        <div class="umkm-theme-option">
                            <input class="umkm-theme-radio" type="radio" name="theme_key" id="theme-{{ $theme['key'] }}" value="{{ $theme['key'] }}" @checked($theme['active'])>
                            <label class="umkm-theme-card umkm-theme-preview--{{ $theme['key'] }}" for="theme-{{ $theme['key'] }}">
                                <span class="umkm-theme-swatch" aria-hidden="true">
                                    <span class="umkm-theme-swatch-row"><span class="umkm-theme-swatch-pill"></span><span class="umkm-theme-swatch-pill"></span><span class="umkm-theme-swatch-pill"></span></span>
                                    <span class="umkm-theme-swatch-row"><span class="umkm-theme-swatch-pill"></span><span class="umkm-theme-swatch-pill"></span></span>
                                </span>
                                <span class="umkm-theme-card-body"><strong>{{ $theme['label'] }}</strong><p>{{ $theme['description'] }}</p></span>
                                <span class="umkm-theme-card-footer">
                                    <span class="umkm-theme-tone">{{ $theme['tone'] }}</span>
                                    @if ($theme['active'])
                                        <span class="umkm-theme-status">Aktif</span>
                                    @endif
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="umkm-theme-security-note">
                    <strong>Catatan keamanan</strong>
                    <span>UI hanya menyediakan pilihan. Nilai theme tetap divalidasi dengan allowlist di backend, dilindungi CSRF, dibatasi role/permission, dan perubahan dicatat ke audit log.</span>
                </div>

                <div class="umkm-theme-actions">
                    <button type="submit" class="btn btn-primary">Simpan Theme Sistem</button>
                </div>
            </form>
        </x-umkm.data-display.table-card>
    </div>
@endsection