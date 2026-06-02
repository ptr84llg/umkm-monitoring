@php
    use Illuminate\Support\Facades\Route;

    $assetProfile = $assetProfile ?? 'full';

    $pageCss = array_values(array_unique(array_merge($pageCss ?? [], [
        'dashboard/dashboard-shell.css',
    ])));

    $pageJs = array_values(array_unique(array_merge($pageJs ?? [], [
        'dashboard/dashboard-shell.js',
    ])));

    $assetModules = array_values(array_unique(array_merge($assetModules ?? [], [
        'session',
    ])));

    $dashboardUser = auth()->user();
    $dashboardUserName = trim((string) ($dashboardUser?->name ?? 'Pengguna'));
    $dashboardUserEmail = $dashboardUser?->email;
    $dashboardUserInitial = strtoupper(substr($dashboardUserName !== '' ? $dashboardUserName : 'U', 0, 1));
    $dashboardCurrentRoute = Route::currentRouteName();
    $dashboardHomeUrl = url('/');
    $dashboardRoleKey = 'user';
    $dashboardRoleLabel = 'Pengguna';
    $dashboardRoleHint = 'Ruang kerja internal';

    if ($dashboardUser?->hasRole('admin_utama')) {
        $dashboardRoleKey = 'admin_utama';
        $dashboardRoleLabel = 'Admin Utama';
        $dashboardRoleHint = 'Kendali sistem dan tata kelola';
    } elseif ($dashboardUser?->hasRole('admin_dinas')) {
        $dashboardRoleKey = 'admin_dinas';
        $dashboardRoleLabel = 'Admin Dinas';
        $dashboardRoleHint = 'Pembinaan dan validasi wilayah';
    } elseif ($dashboardUser?->hasRole('kepala_dinas')) {
        $dashboardRoleKey = 'kepala_dinas';
        $dashboardRoleLabel = 'Kepala Dinas';
        $dashboardRoleHint = 'Monitoring eksekutif';
    } elseif ($dashboardUser?->hasRole('pelaku_umkm')) {
        $dashboardRoleKey = 'pelaku_umkm';
        $dashboardRoleLabel = 'Pelaku UMKM';
        $dashboardRoleHint = 'Data usaha dan pelaporan';
    } elseif ($dashboardUser?->hasRole('validator_ahli')) {
        $dashboardRoleKey = 'validator_ahli';
        $dashboardRoleLabel = 'Validator Ahli';
        $dashboardRoleHint = 'Validasi instrumen dan artefak';
    }

    $dashboardHomeCandidates = [
        'admin_utama' => 'admin-utama.dashboard',
        'admin_dinas' => 'admin-dinas.dashboard',
        'kepala_dinas' => 'kepala-dinas.dashboard',
        'pelaku_umkm' => 'pelaku-umkm.dashboard',
        'validator_ahli' => 'expert.validator.list',
    ];

    $dashboardHomeRoute = $dashboardHomeCandidates[$dashboardRoleKey] ?? null;

    if (is_string($dashboardHomeRoute) && Route::has($dashboardHomeRoute)) {
        $dashboardHomeUrl = route($dashboardHomeRoute);
    }

    $internalMenuBlueprint = [
        'admin_utama' => [
            [
                'label' => 'Utama',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'description' => 'Kendali sistem',
                        'route' => 'admin-utama.dashboard',
                        'permission' => 'dashboard.view.executive',
                        'icon' => 'dashboard',
                    ],
                ],
            ],
            [
                'label' => 'Tata Kelola',
                'items' => [
                    [
                        'title' => 'Akses',
                        'description' => 'Akun, role, permission',
                        'route' => null,
                        'permission' => 'access.manage',
                        'icon' => 'shield',
                    ],
                    [
                        'title' => 'Referensi',
                        'description' => 'Wilayah, KBLI, master data',
                        'route' => null,
                        'permission' => 'reference.manage',
                        'icon' => 'database',
                    ],
                    [
                        'title' => 'Governance',
                        'description' => 'Setting, keamanan, audit',
                        'route' => null,
                        'permission' => 'system.manage',
                        'icon' => 'settings',
                    ],
                    [
                        'title' => 'Publikasi',
                        'description' => 'Pengumuman dan konten',
                        'route' => null,
                        'permission' => 'content.manage',
                        'icon' => 'megaphone',
                    ],
                    [
                        'title' => 'Validasi',
                        'description' => 'Survei dan ahli',
                        'route' => null,
                        'permission' => 'validation.manage',
                        'icon' => 'check',
                    ],
                ],
            ],
        ],
        'admin_dinas' => [
            [
                'label' => 'Operasional',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'description' => 'Ringkasan pembinaan',
                        'route' => 'admin-dinas.dashboard',
                        'permission' => null,
                        'icon' => 'dashboard',
                    ],
                    [
                        'title' => 'Data UMKM',
                        'description' => 'Validasi dan pembinaan',
                        'route' => null,
                        'permission' => 'umkm.verify',
                        'icon' => 'store',
                    ],
                    [
                        'title' => 'Analitik',
                        'description' => 'Kinerja dan wilayah',
                        'route' => null,
                        'permission' => 'dashboard.view.operational',
                        'icon' => 'chart',
                    ],
                ],
            ],
        ],
        'kepala_dinas' => [
            [
                'label' => 'Eksekutif',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'description' => 'Monitoring keputusan',
                        'route' => 'kepala-dinas.dashboard',
                        'permission' => null,
                        'icon' => 'dashboard',
                    ],
                    [
                        'title' => 'Laporan',
                        'description' => 'Ringkasan strategis',
                        'route' => null,
                        'permission' => 'report.view.executive',
                        'icon' => 'document',
                    ],
                ],
            ],
        ],
        'pelaku_umkm' => [
            [
                'label' => 'Usaha Saya',
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'description' => 'Ringkasan usaha',
                        'route' => 'pelaku-umkm.dashboard',
                        'permission' => null,
                        'icon' => 'dashboard',
                    ],
                    [
                        'title' => 'Profil Usaha',
                        'description' => 'Identitas dan lokasi',
                        'route' => null,
                        'permission' => 'umkm.profile.manage',
                        'icon' => 'store',
                    ],
                    [
                        'title' => 'Pelaporan',
                        'description' => 'Kinerja dan transaksi',
                        'route' => null,
                        'permission' => 'umkm.report.submit',
                        'icon' => 'document',
                    ],
                ],
            ],
        ],
        'validator_ahli' => [
            [
                'label' => 'Validasi Ahli',
                'items' => [
                    [
                        'title' => 'Instrumen',
                        'description' => 'Daftar validasi',
                        'route' => 'expert.validator.list',
                        'permission' => 'validation.expert.fill',
                        'icon' => 'check',
                    ],
                    [
                        'title' => 'Riwayat',
                        'description' => 'Penilaian tersimpan',
                        'route' => null,
                        'permission' => 'validation.expert.fill',
                        'icon' => 'document',
                    ],
                ],
            ],
        ],
    ];

    $dashboardMenuSections = $internalMenuBlueprint[$dashboardRoleKey] ?? [
        [
            'label' => 'Ruang Kerja',
            'items' => [
                [
                    'title' => 'Dashboard',
                    'description' => 'Menu belum dikonfigurasi',
                    'route' => $dashboardHomeRoute,
                    'permission' => null,
                    'icon' => 'dashboard',
                ],
            ],
        ],
    ];

    $dashboardRoleBadgeClass = [
        'admin_utama' => 'dashboard-role-admin-utama',
        'admin_dinas' => 'dashboard-role-admin-dinas',
        'kepala_dinas' => 'dashboard-role-kepala-dinas',
        'pelaku_umkm' => 'dashboard-role-pelaku-umkm',
        'validator_ahli' => 'dashboard-role-validator-ahli',
    ][$dashboardRoleKey] ?? 'dashboard-role-user';
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
<body class="layout-dashboard"
      data-dashboard-shell
      data-umkm-session-guard
      data-umkm-session-lifetime-minutes="{{ (int) config('session.lifetime', 60) }}"
      data-umkm-session-warning-seconds="{{ (int) config('umkm.security.session_warning_seconds', 300) }}"
      data-umkm-session-redirect-url="{{ url('/') }}"
      data-umkm-session-keep-alive-url="{{ route('session.keep-alive') }}">
    <div class="dashboard-shell" data-dashboard-shell-frame data-sidebar-state="expanded" data-sidebar-mobile="closed">
        <header class="dashboard-topbar" data-dashboard-topbar>
            <div class="dashboard-topbar-inner">
                <div class="dashboard-topbar-start">
                    <button type="button"
                            class="dashboard-icon-button dashboard-sidebar-toggle"
                            data-dashboard-sidebar-toggle
                            aria-label="Buka atau tutup menu samping"
                            aria-controls="dashboard-sidebar"
                            aria-expanded="true">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 6.5h16v2H4v-2Zm0 4.5h16v2H4v-2Zm0 4.5h16v2H4v-2Z"/>
                        </svg>
                    </button>

                    <a class="dashboard-brand" href="{{ $dashboardHomeUrl }}" aria-label="Ruang Kerja Monitoring UMKM">
                        <span class="dashboard-brand-mark">MU</span>
                        <span class="dashboard-brand-copy">
                            <strong>Ruang Kerja</strong>
                            <small>Monitoring UMKM</small>
                        </span>
                    </a>
                </div>

                <div class="dashboard-topbar-actions">
                    <a class="dashboard-topbar-link d-none d-lg-inline-flex" href="{{ url('/') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3 3 10.5V21h6v-6h6v6h6V10.5L12 3Z"/>
                        </svg>
                        <span>Beranda Publik</span>
                    </a>

                    <a class="dashboard-topbar-link d-none d-md-inline-flex" href="{{ $dashboardHomeUrl }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/>
                        </svg>
                        <span>Ruang Kerja</span>
                    </a>

                    <div class="dashboard-notification-wrap">
                        <button type="button"
                                class="dashboard-icon-button dashboard-notification-button"
                                data-dashboard-panel-toggle="notifications"
                                aria-label="Tampilkan ringkasan aktivitas"
                                aria-expanded="false">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 0 0-5-6.7V3a2 2 0 1 0-4 0v1.3A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2Z"/>
                            </svg>
                            <span class="dashboard-dot" aria-hidden="true"></span>
                        </button>

                        <div class="dashboard-floating-panel" data-dashboard-panel="notifications" hidden>
                            <div class="dashboard-floating-panel-header">
                                <strong>Aktivitas</strong>
                                <span>Ringkas</span>
                            </div>
                            <div class="dashboard-floating-panel-body">
                                <p>Panel aktivitas internal disiapkan sebagai shell. Data detail akan dimuat melalui modul terotorisasi pada batch berikutnya.</p>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-user-chip {{ $dashboardRoleBadgeClass }}" title="{{ $dashboardUserEmail }}">
                        <span class="dashboard-user-avatar">{{ $dashboardUserInitial }}</span>
                        <span class="dashboard-user-copy">
                            <strong>{{ $dashboardUserName }}</strong>
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

        <div class="dashboard-workspace">
            <aside id="dashboard-sidebar" class="dashboard-sidebar" data-dashboard-sidebar aria-label="Menu ruang kerja internal">
                <div class="dashboard-sidebar-inner">
                    <div class="dashboard-sidebar-profile">
                        <div class="dashboard-sidebar-avatar">{{ $dashboardUserInitial }}</div>
                        <div class="dashboard-sidebar-profile-copy">
                            <strong>{{ $dashboardRoleLabel }}</strong>
                            <small>{{ $dashboardRoleHint }}</small>
                        </div>
                    </div>

                    <nav class="dashboard-menu" aria-label="Navigasi internal">
                        @foreach ($dashboardMenuSections as $section)
                            <section class="dashboard-menu-section">
                                <div class="dashboard-menu-label">{{ $section['label'] ?? 'Menu' }}</div>

                                <div class="dashboard-menu-list">
                                    @foreach (($section['items'] ?? []) as $menuItem)
                                        @php
                                            $menuRoute = $menuItem['route'] ?? null;
                                            $menuPermission = $menuItem['permission'] ?? null;
                                            $menuIcon = $menuItem['icon'] ?? 'dashboard';
                                            $routeExists = is_string($menuRoute) && Route::has($menuRoute);
                                            $permissionAllowed = empty($menuPermission) || (bool) $dashboardUser?->hasPermission($menuPermission);
                                            $menuEnabled = $routeExists && $permissionAllowed;
                                            $menuHref = $menuEnabled ? route($menuRoute) : '#';
                                            $menuActive = $routeExists && is_string($dashboardCurrentRoute) && $dashboardCurrentRoute === $menuRoute;
                                        @endphp

                                        <a href="{{ $menuHref }}"
                                           class="dashboard-menu-item {{ $menuActive ? 'is-active' : '' }} {{ $menuEnabled ? '' : 'is-disabled' }}"
                                           @if (! $menuEnabled) aria-disabled="true" tabindex="-1" @endif
                                           title="{{ $menuItem['description'] ?? $menuItem['title'] ?? 'Menu' }}">
                                            <span class="dashboard-menu-icon" aria-hidden="true">
                                                @switch($menuIcon)
                                                    @case('shield')
                                                        <svg viewBox="0 0 24 24"><path d="M12 2 5 5v6c0 5 3 9 7 11 4-2 7-6 7-11V5l-7-3Zm0 4 3 1.3V11c0 3-1.4 5.4-3 6.8-1.6-1.4-3-3.8-3-6.8V7.3L12 6Z"/></svg>
                                                        @break
                                                    @case('database')
                                                        <svg viewBox="0 0 24 24"><path d="M12 3C7 3 4 4.8 4 7v10c0 2.2 3 4 8 4s8-1.8 8-4V7c0-2.2-3-4-8-4Zm0 2c4 0 6 1.2 6 2s-2 2-6 2-6-1.2-6-2 2-2 6-2Zm0 14c-4 0-6-1.2-6-2v-2.2c1.4.9 3.5 1.2 6 1.2s4.6-.3 6-1.2V17c0 .8-2 2-6 2Zm0-5c-4 0-6-1.2-6-2V9.8c1.4.9 3.5 1.2 6 1.2s4.6-.3 6-1.2V12c0 .8-2 2-6 2Z"/></svg>
                                                        @break
                                                    @case('settings')
                                                        <svg viewBox="0 0 24 24"><path d="M19.4 13.5c.1-.5.1-1 .1-1.5s0-1-.1-1.5l2.1-1.6-2-3.5-2.5 1a8 8 0 0 0-2.6-1.5L14 2h-4l-.4 2.9A8 8 0 0 0 7 6.4l-2.5-1-2 3.5 2.1 1.6c-.1.5-.1 1-.1 1.5s0 1 .1 1.5l-2.1 1.6 2 3.5 2.5-1a8 8 0 0 0 2.6 1.5L10 22h4l.4-2.9a8 8 0 0 0 2.6-1.5l2.5 1 2-3.5-2.1-1.6ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"/></svg>
                                                        @break
                                                    @case('megaphone')
                                                        <svg viewBox="0 0 24 24"><path d="M21 4v14h-2l-8-4H7v4H4v-4H3V8h8l8-4h2ZM7 10v2h4.5l5.5 2.8V7.2L11.5 10H7Z"/></svg>
                                                        @break
                                                    @case('check')
                                                        <svg viewBox="0 0 24 24"><path d="M9.5 16.2 5.8 12.5 4.4 13.9l5.1 5.1L20 8.5 18.6 7.1 9.5 16.2Z"/></svg>
                                                        @break
                                                    @case('chart')
                                                        <svg viewBox="0 0 24 24"><path d="M4 19h16v2H2V3h2v16Zm3-2V9h3v8H7Zm5 0V5h3v12h-3Zm5 0v-6h3v6h-3Z"/></svg>
                                                        @break
                                                    @case('store')
                                                        <svg viewBox="0 0 24 24"><path d="M4 4h16l1 6v2h-1v8H4v-8H3v-2l1-6Zm2 10v4h12v-4H6Zm-.6-8-.6 4h14.4l-.6-4H5.4Z"/></svg>
                                                        @break
                                                    @case('document')
                                                        <svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6V2Zm8 1.5V8h4.5L14 3.5ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>
                                                        @break
                                                    @default
                                                        <svg viewBox="0 0 24 24"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"/></svg>
                                                @endswitch
                                            </span>
                                            <span class="dashboard-menu-copy">
                                                <strong>{{ $menuItem['title'] ?? 'Menu' }}</strong>
                                                <small>{{ $menuItem['description'] ?? 'Belum tersedia' }}</small>
                                            </span>
                                            @if (! $menuEnabled)
                                                <span class="dashboard-menu-state">Soon</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </nav>
                </div>
            </aside>

            <div class="dashboard-sidebar-backdrop" data-dashboard-sidebar-backdrop aria-hidden="true"></div>

            <main class="dashboard-main">
                <div class="dashboard-main-inner">
                    <section class="dashboard-page-head">
                        <div>
                            <span class="dashboard-page-kicker">{{ $dashboardRoleLabel }}</span>
                            <h1>@yield('page_title', View::yieldContent('title', 'Ruang Kerja'))</h1>
                        </div>
                        <div class="dashboard-page-meta">
                            <span>{{ now()->translatedFormat('d M Y') }}</span>
                            <span>{{ $dashboardRoleHint }}</span>
                        </div>
                    </section>

                    <div class="dashboard-content">
                        @yield('content')
                    </div>

                    <footer class="dashboard-footer">
                        <span>Monitoring UMKM</span>
                        <span>Internal layout core · backend guard tetap otoritas final</span>
                    </footer>
                </div>
            </main>
        </div>
    </div>
</body>
</html>