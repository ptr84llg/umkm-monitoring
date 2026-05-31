@php
    use Illuminate\Support\Facades\Route;

    $assetProfile = $assetProfile ?? 'full';
    $pageCss = array_values(array_unique(array_merge($pageCss ?? [], [
        'dashboard/dashboard-shell.css',
    ])));

    $dashboardUser = auth()->user();
    $dashboardHomeUrl = url('/');

    if ($dashboardUser?->hasRole('admin_utama') && Route::has('admin-utama.dashboard')) {
        $dashboardHomeUrl = route('admin-utama.dashboard');
    } elseif ($dashboardUser?->hasRole('admin_dinas') && Route::has('admin-dinas.dashboard')) {
        $dashboardHomeUrl = route('admin-dinas.dashboard');
    } elseif ($dashboardUser?->hasRole('kepala_dinas') && Route::has('kepala-dinas.dashboard')) {
        $dashboardHomeUrl = route('kepala-dinas.dashboard');
    } elseif ($dashboardUser?->hasRole('pelaku_umkm') && Route::has('pelaku-umkm.dashboard')) {
        $dashboardHomeUrl = route('pelaku-umkm.dashboard');
    } elseif ($dashboardUser?->hasRole('validator_ahli') && Route::has('expert.validator.list')) {
        $dashboardHomeUrl = route('expert.validator.list');
    }

    $dashboardRoleLabel = 'Pengguna';

    if ($dashboardUser?->hasRole('admin_utama')) {
        $dashboardRoleLabel = 'Admin Utama';
    } elseif ($dashboardUser?->hasRole('admin_dinas')) {
        $dashboardRoleLabel = 'Admin Dinas';
    } elseif ($dashboardUser?->hasRole('kepala_dinas')) {
        $dashboardRoleLabel = 'Kepala Dinas';
    } elseif ($dashboardUser?->hasRole('pelaku_umkm')) {
        $dashboardRoleLabel = 'Pelaku UMKM';
    } elseif ($dashboardUser?->hasRole('validator_ahli')) {
        $dashboardRoleLabel = 'Validator Ahli';
    }
@endphp
<!doctype html>
<html lang="id" data-umkm-theme="{{ $activeTheme ?? 'green' }}">
<head>
    <x-umkm.seo-meta area="private" robots="noindex,nofollow,noarchive,nosnippet" :render-title="false" :render-description="false" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="umkm-client" content="dashboard">
    <meta name="umkm-security-profile" content="{{ $assetProfile }}">
    <title>@yield('title', 'Ruang Kerja | Monitoring UMKM')</title>
    @include('partials.asset-loader')
</head>
<body class="layout-dashboard">
    <div class="dashboard-shell">
        <header class="dashboard-topbar" data-dashboard-topbar>
    <x-umkm.seo-meta area="private" robots="noindex,nofollow,noarchive,nosnippet" :render-title="false" :render-description="false" />
            <div class="dashboard-topbar-inner">
                <a class="dashboard-brand" href="{{ $dashboardHomeUrl }}" aria-label="Ruang Kerja Monitoring UMKM">
                    <span class="dashboard-brand-mark">MU</span>
                    <span class="dashboard-brand-copy">
                        <strong>Ruang Kerja</strong>
                        <small>Monitoring UMKM</small>
                    </span>
                </a>

                <div class="dashboard-topbar-actions">
                    <a class="dashboard-topbar-link d-none d-md-inline-flex" href="{{ url('/') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3 3 10.5V21h6v-6h6v6h6V10.5L12 3Z"/>
                        </svg>
                        <span>Beranda Publik</span>
                    </a>

                    <a class="dashboard-topbar-link d-none d-sm-inline-flex" href="{{ $dashboardHomeUrl }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/>
                        </svg>
                        <span>Ruang Kerja</span>
                    </a>

                    <div class="dashboard-user-chip" title="{{ $dashboardUser?->email }}">
                        <span class="dashboard-user-avatar">
                            {{ strtoupper(substr($dashboardUser?->name ?? 'U', 0, 1)) }}
                        </span>
                        <span class="dashboard-user-copy">
                            <strong>{{ $dashboardUser?->name ?? 'Pengguna' }}</strong>
                            <small>{{ $dashboardRoleLabel }}</small>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="dashboard-logout-form">
                        @csrf
                        <button type="submit" class="dashboard-logout-btn">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M16 13v-2H7V8l-5 4 5 4v-3h9Zm-2-10h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6v-2h6V5h-6V3Z"/>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <div class="dashboard-content">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>

