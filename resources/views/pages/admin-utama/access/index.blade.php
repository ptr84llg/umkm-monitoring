@extends('layouts.dashboard')

@section('title', 'Admin Utama - Manajemen Akses')
@section('page_title', 'Manajemen Akses')

@section('content')
    @php
        $accessIcon = function (string $name): string {
            return match ($name) {
                'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 5 5v6c0 5 3 9 7 11 4-2 7-6 7-11V5l-7-3Zm0 4 3 1.3V11c0 2.8-1.2 5-3 6.4C10.2 16 9 13.8 9 11V7.3L12 6Z"/></svg>',
                'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.3 0-6 1.8-6 4v2h12v-2c0-2.2-2.7-4-6-4Zm7.5-1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm0 1.5c-.7 0-1.4.1-2 .3 1.5.9 2.5 2.1 2.5 3.7V19h5v-1.8c0-2-2.5-3.7-5.5-3.7Z"/></svg>',
                'key' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 14a5 5 0 1 1 4.6-7h9.4v4h-3v3h-3v-3h-3.4A5 5 0 0 1 7 14Zm0-3a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>',
                'lock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-7 0V7a2 2 0 1 1 4 0v2h-4Zm3 7.7V18h-2v-1.3a2 2 0 1 1 2 0Z"/></svg>',
                'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9.2 16.6-4-4L3.8 14l5.4 5.4L20.6 8 19.2 6.6 9.2 16.6Z"/></svg>',
                'grid' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z"/></svg>',
                'roles' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 7v5c0 4.4 3.2 7.7 8 9 4.8-1.3 8-4.6 8-9V7l-8-4Zm0 3.2 5 2.5V12c0 2.8-1.8 5-5 6.2C8.8 17 7 14.8 7 12V8.7l5-2.5Zm0 2.8a3 3 0 0 0-1 5.8V16h2v-1.2A3 3 0 0 0 12 9Z"/></svg>',
                'audit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h10l4 4v14H5V3Zm9 1.5V8h3.5L14 4.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h4v2H8V7Z"/></svg>',
                'filter' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7L4 5Z"/></svg>',
                'eye' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5 0 8.5 4.5 9.7 6.4.2.4.2.8 0 1.2C20.5 14.5 17 19 12 19s-8.5-4.5-9.7-6.4a1.2 1.2 0 0 1 0-1.2C3.5 9.5 7 5 12 5Zm0 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z"/></svg>',
                'activity' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h4l2-6 4 10 2-4h4v2h-2.8L14 21 10 11 8.8 15H4v-2Z"/></svg>',
                default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 .1 0H12Zm1 15h-2v-2h2v2Zm0-4h-2V7h2v6Z"/></svg>',
            };
        };
    @endphp

    <div class="umkm-access-management">
        <section class="umkm-access-hero">
            <div class="umkm-access-hero-main">
                <span class="umkm-access-hero-icon" aria-hidden="true">{!! $accessIcon('shield') !!}</span>
                <div>
                    <span class="umkm-access-kicker">Access Governance</span>
                    <h2>Manajemen Akses Sistem</h2>
                    <p>
                        Modul ini menjadi pusat kendali akun, role, permission, assignment, sesi/perangkat,
                        dan audit akses. Pada tahap ini, akun sudah ditampilkan dengan detail read-only,
                        filter aman, dan modal informasi tanpa membuka aksi perubahan.
                    </p>
                </div>
            </div>

            <div class="umkm-access-guard-card">
                <span class="umkm-access-mini-icon" aria-hidden="true">{!! $accessIcon('lock') !!}</span>
                <small>Backend Guard</small>
                <strong>RBAC + PBAC + Audit</strong>
                <span>Menu mengikuti role/permission, tetapi backend tetap menjadi pengunci akhir.</span>
            </div>
        </section>

        <section class="umkm-access-zone umkm-access-zone--summary">
            <div class="umkm-access-zone-header">
                <div class="umkm-access-zone-title">
                    <span class="umkm-access-zone-icon" aria-hidden="true">{!! $accessIcon('activity') !!}</span>
                    <div>
                        <span class="umkm-access-kicker">Ringkasan</span>
                        <h3>Status Akses Sistem</h3>
                        <p>Ikhtisar kondisi akun, autentikasi, permission, dan jejak keamanan.</p>
                    </div>
                </div>
                <span class="umkm-access-soft-badge">Read-only</span>
            </div>

            <div class="umkm-access-stat-grid" aria-label="Ringkasan akses">
                <article class="umkm-access-stat-card">
                    <span class="umkm-access-stat-icon" aria-hidden="true">{!! $accessIcon('users') !!}</span>
                    <div>
                        <span>Total Akun</span>
                        <strong>{{ number_format($accessStats['users_total']) }}</strong>
                        <small>{{ number_format($accessStats['users_active']) }} aktif · {{ number_format($accessStats['users_inactive']) }} nonaktif</small>
                    </div>
                </article>
                <article class="umkm-access-stat-card">
                    <span class="umkm-access-stat-icon" aria-hidden="true">{!! $accessIcon('key') !!}</span>
                    <div>
                        <span>Google Linked</span>
                        <strong>{{ number_format($accessStats['users_google_linked']) }}</strong>
                        <small>{{ number_format($accessStats['users_google_required']) }} wajib Google</small>
                    </div>
                </article>
                <article class="umkm-access-stat-card">
                    <span class="umkm-access-stat-icon" aria-hidden="true">{!! $accessIcon('roles') !!}</span>
                    <div>
                        <span>Permission</span>
                        <strong>{{ number_format($accessStats['permissions_total']) }}</strong>
                        <small>{{ number_format($accessStats['role_permissions']) }} role-permission</small>
                    </div>
                </article>
                <article class="umkm-access-stat-card">
                    <span class="umkm-access-stat-icon" aria-hidden="true">{!! $accessIcon('audit') !!}</span>
                    <div>
                        <span>Audit & Security</span>
                        <strong>{{ number_format($accessStats['security_events']) }}</strong>
                        <small>{{ number_format($accessStats['audit_logs']) }} audit log</small>
                    </div>
                </article>
            </div>
        </section>

        <section class="umkm-access-zone umkm-access-zone--map">
            <div class="umkm-access-zone-header">
                <div class="umkm-access-zone-title">
                    <span class="umkm-access-zone-icon" aria-hidden="true">{!! $accessIcon('grid') !!}</span>
                    <div>
                        <span class="umkm-access-kicker">Peta Modul</span>
                        <h3>Area Kendali Akses</h3>
                        <p>Setiap area dipisahkan agar struktur pengelolaan akses tidak tampak menumpuk.</p>
                    </div>
                </div>
                <span class="umkm-access-soft-badge">Module map</span>
            </div>

            <div class="umkm-access-section-grid">
                @foreach ($accessSections as $section)
                    @php
                        $sectionIcon = match ($section['key']) {
                            'accounts' => 'users',
                            'roles' => 'roles',
                            'permissions' => 'key',
                            'assignment' => 'check',
                            'sessions' => 'lock',
                            'audit' => 'audit',
                            default => 'grid',
                        };
                    @endphp
                    <article class="umkm-access-section-card">
                        <div class="umkm-access-section-top">
                            <span class="umkm-access-section-icon" aria-hidden="true">{!! $accessIcon($sectionIcon) !!}</span>
                            <span class="umkm-access-section-status">{{ $section['status'] }}</span>
                        </div>
                        <h3>{{ $section['title'] }}</h3>
                        <p>{{ $section['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="umkm-access-zone umkm-access-zone--accounts">
            <div class="umkm-access-zone-header">
                <div class="umkm-access-zone-title">
                    <span class="umkm-access-zone-icon" aria-hidden="true">{!! $accessIcon('users') !!}</span>
                    <div>
                        <span class="umkm-access-kicker">Akun</span>
                        <h3>Akun Pengguna</h3>
                        <p>Filter membaca data minimal akun tanpa membuka aksi tambah, edit, delete, assign role, atau revoke session.</p>
                    </div>
                </div>
                <span class="umkm-access-soft-badge">Read-only detail</span>
            </div>

            <x-umkm.data-display.table-card>
                <form method="GET" action="{{ route('admin-utama.access.index') }}" class="umkm-access-filter" autocomplete="off">
                    <div>
                        <label for="filter-q"><span class="umkm-access-inline-icon" aria-hidden="true">{!! $accessIcon('filter') !!}</span> Cari akun</label>
                        <input
                            id="filter-q"
                            type="search"
                            name="q"
                            value="{{ $filters['q'] }}"
                            class="form-control"
                            placeholder="Nama, email, atau username"
                        >
                    </div>

                    <div>
                        <label for="filter-status">Status akun</label>
                        <select id="filter-status" name="status" class="form-select">
                            <option value="all" @selected($filters['status'] === 'all')>Semua status</option>
                            <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                            <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                        </select>
                    </div>

                    <div>
                        <label for="filter-role">Role</label>
                        <select id="filter-role" name="role" class="form-select">
                            <option value="all" @selected($filters['role'] === 'all')>Semua role</option>
                            @foreach ($roleOptions as $role)
                                <option value="{{ $role->code }}" @selected($filters['role'] === $role->code)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter-auth">Login</label>
                        <select id="filter-auth" name="auth" class="form-select">
                            <option value="all" @selected($filters['auth'] === 'all')>Semua login</option>
                            <option value="google_linked" @selected($filters['auth'] === 'google_linked')>Google linked</option>
                            <option value="google_required" @selected($filters['auth'] === 'google_required')>Wajib Google</option>
                            <option value="manual_only" @selected($filters['auth'] === 'manual_only')>Manual only</option>
                        </select>
                    </div>

                    <div class="umkm-access-filter-actions">
                        <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                        <a href="{{ route('admin-utama.access.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle umkm-access-table umkm-access-account-table">
                        <thead>
                            <tr>
                                <th>Akun</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Login</th>
                                <th>Login Terakhir</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($accounts as $account)
                                @php
                                    $roleLabels = $account->roles->pluck('name')->filter()->values();
                                    $isGoogleLinked = filled($account->google_linked_at);
                                    $isGoogleRequired = $account->auth_provider_required === \App\Models\User::AUTH_PROVIDER_GOOGLE
                                        && filled($account->manual_login_disabled_at);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="umkm-access-account-cell">
                                            <strong>{{ $account->name }}</strong>
                                            <span>{{ $account->email }}</span>
                                            @if ($account->username)
                                                <small>{{ $account->username }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="umkm-access-role-chip-list">
                                            @forelse ($roleLabels as $roleLabel)
                                                <span class="umkm-access-badge is-info">{{ $roleLabel }}</span>
                                            @empty
                                                <span class="umkm-access-badge is-muted">Belum ada role</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        <span class="umkm-access-badge {{ $account->is_active ? 'is-success' : 'is-muted' }}">
                                            {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="umkm-access-role-chip-list">
                                            <span class="umkm-access-badge {{ $isGoogleLinked ? 'is-info' : 'is-muted' }}">
                                                {{ $isGoogleLinked ? 'Google linked' : 'Belum linked' }}
                                            </span>
                                            @if ($isGoogleRequired)
                                                <span class="umkm-access-badge is-warning">Wajib Google</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $account->last_login_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#accountDetailModal{{ $account->id }}"
                                        >
                                            <span class="umkm-access-button-icon" aria-hidden="true">{!! $accessIcon('eye') !!}</span>
                                            Lihat
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">Tidak ada akun yang sesuai filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach ($accounts as $account)
                    @php
                        $roleLabels = $account->roles->pluck('name')->filter()->values();
                        $isGoogleLinked = filled($account->google_linked_at);
                    @endphp
                    <div class="modal fade" id="accountDetailModal{{ $account->id }}" tabindex="-1" aria-labelledby="accountDetailModalLabel{{ $account->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content umkm-access-detail-modal">
                                <div class="modal-header">
                                    <div class="umkm-access-modal-title">
                                        <span class="umkm-access-zone-icon" aria-hidden="true">{!! $accessIcon('users') !!}</span>
                                        <div>
                                            <span class="umkm-access-kicker">Detail Akun Read-only</span>
                                            <h5 class="modal-title" id="accountDetailModalLabel{{ $account->id }}">{{ $account->name }}</h5>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="umkm-access-detail-grid">
                                        <div>
                                            <span>Nama</span>
                                            <strong>{{ $account->name }}</strong>
                                        </div>
                                        <div>
                                            <span>Email</span>
                                            <strong>{{ $account->email }}</strong>
                                        </div>
                                        <div>
                                            <span>Username</span>
                                            <strong>{{ $account->username ?? '-' }}</strong>
                                        </div>
                                        <div>
                                            <span>Status akun</span>
                                            <strong>{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</strong>
                                        </div>
                                        <div>
                                            <span>Google linked</span>
                                            <strong>{{ $isGoogleLinked ? $account->google_linked_at?->format('d M Y H:i') : 'Belum linked' }}</strong>
                                        </div>
                                        <div>
                                            <span>Manual login</span>
                                            <strong>{{ $account->manual_login_disabled_at ? 'Dinonaktifkan' : 'Diizinkan' }}</strong>
                                        </div>
                                        <div>
                                            <span>Login terakhir</span>
                                            <strong>{{ $account->last_login_at?->format('d M Y H:i') ?? '-' }}</strong>
                                        </div>
                                        <div>
                                            <span>Dibuat</span>
                                            <strong>{{ $account->created_at?->format('d M Y H:i') ?? '-' }}</strong>
                                        </div>
                                    </div>

                                    <div class="umkm-access-detail-roles">
                                        <span>Role terhubung</span>
                                        <div class="umkm-access-role-chip-list">
                                            @forelse ($roleLabels as $roleLabel)
                                                <span class="umkm-access-badge is-info">{{ $roleLabel }}</span>
                                            @empty
                                                <span class="umkm-access-badge is-muted">Belum ada role</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="umkm-access-security-note is-compact">
                                        <strong>Batas Access-1B</strong>
                                        <span>
                                            Modal ini hanya membaca data minimal akun. Perubahan status, role, permission,
                                            session revoke, dan assignment belum dibuka pada tahap ini.
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="umkm-access-pagination">
                    {{ $accounts->links() }}
                </div>
            </x-umkm.data-display.table-card>
        </section>

        <section class="umkm-access-zone umkm-access-zone--matrix">
            <div class="umkm-access-zone-header">
                <div class="umkm-access-zone-title">
                    <span class="umkm-access-zone-icon" aria-hidden="true">{!! $accessIcon('roles') !!}</span>
                    <div>
                        <span class="umkm-access-kicker">Matriks</span>
                        <h3>Matriks Role & Permission</h3>
                        <p>Role dan permission diberi zona tersendiri agar hubungan kewenangan lebih mudah dibaca.</p>
                    </div>
                </div>
                <span class="umkm-access-soft-badge">RBAC / PBAC</span>
            </div>

            <div class="umkm-access-grid-two">
                <x-umkm.data-display.table-card>
                    <div class="umkm-access-card-head">
                        <div>
                            <span class="umkm-access-kicker">Role</span>
                            <h3>Role Sistem</h3>
                        </div>
                        <span class="umkm-access-soft-badge">Matrix awal</span>
                    </div>

                    <div class="umkm-access-role-list">
                        @forelse ($roleSummary as $role)
                            <div class="umkm-access-role-item">
                                <div>
                                    <strong>{{ $role->name }}</strong>
                                    <span>{{ $role->code }}</span>
                                </div>
                                <div>
                                    <span class="umkm-access-badge {{ $role->is_active ? 'is-success' : 'is-muted' }}">
                                        {{ $role->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                    <span class="umkm-access-badge is-info">
                                        {{ $role->permissions_count }} permission
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Belum ada role.</p>
                        @endforelse
                    </div>
                </x-umkm.data-display.table-card>

                <x-umkm.data-display.table-card>
                    <div class="umkm-access-card-head">
                        <div>
                            <span class="umkm-access-kicker">Permission</span>
                            <h3>Kelompok Permission</h3>
                        </div>
                        <span class="umkm-access-soft-badge">PBAC</span>
                    </div>

                    <div class="umkm-access-module-list">
                        @forelse ($permissionModules as $module)
                            <div class="umkm-access-module-item">
                                <span>{{ $module->module ?? 'general' }}</span>
                                <strong>{{ number_format($module->total) }}</strong>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Belum ada permission.</p>
                        @endforelse
                    </div>
                </x-umkm.data-display.table-card>
            </div>
        </section>

        <section class="umkm-access-zone umkm-access-zone--audit">
            <div class="umkm-access-zone-header">
                <div class="umkm-access-zone-title">
                    <span class="umkm-access-zone-icon" aria-hidden="true">{!! $accessIcon('audit') !!}</span>
                    <div>
                        <span class="umkm-access-kicker">Audit</span>
                        <h3>Aktivitas Akses Terbaru</h3>
                        <p>Jejak event keamanan ditampilkan sebagai pratinjau read-only untuk tata kelola akses.</p>
                    </div>
                </div>
                <span class="umkm-access-soft-badge">Security trail</span>
            </div>

            <x-umkm.data-display.table-card>
                <div class="umkm-access-timeline">
                    @forelse ($recentSecurityEvents as $event)
                        <div class="umkm-access-timeline-item">
                            <span class="umkm-access-dot"></span>
                            <div>
                                <strong>{{ $event->event_type }}</strong>
                                <small>{{ $event->severity }} · {{ $event->event_time?->format('d M Y H:i') ?? '-' }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada security event.</p>
                    @endforelse
                </div>
            </x-umkm.data-display.table-card>
        </section>

        <section class="umkm-access-security-note">
            <strong>Catatan batch Access-1B-UI1</strong>
            <span>
                Pembaruan ini hanya memperjelas hirarki visual dan menambahkan icon/simbol themeable.
                Aksi tambah, edit, assign role, revoke session, dan perubahan permission tetap belum dibuka.
            </span>
        </section>
    </div>
@endsection