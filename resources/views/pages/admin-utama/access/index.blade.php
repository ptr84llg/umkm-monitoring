@extends('layouts.dashboard')

@section('title', 'Admin Utama - Manajemen Akses')
@section('page_title', 'Manajemen Akses')

@section('content')
    <div class="umkm-access-management">
        <section class="umkm-access-hero">
            <div>
                <span class="umkm-access-kicker">Access Governance</span>
                <h2>Manajemen Akses Sistem</h2>
                <p>
                    Modul ini menjadi pusat kendali akun, role, permission, assignment, sesi/perangkat,
                    dan audit akses. Pada tahap ini, akun sudah ditampilkan dengan detail read-only,
                    filter aman, dan modal informasi tanpa membuka aksi perubahan.
                </p>
            </div>

            <div class="umkm-access-guard-card">
                <small>Backend Guard</small>
                <strong>RBAC + PBAC + Audit</strong>
                <span>Menu mengikuti role/permission, tetapi backend tetap menjadi pengunci akhir.</span>
            </div>
        </section>

        <section class="umkm-access-stat-grid" aria-label="Ringkasan akses">
            <article class="umkm-access-stat-card">
                <span>Total Akun</span>
                <strong>{{ number_format($accessStats['users_total']) }}</strong>
                <small>{{ number_format($accessStats['users_active']) }} aktif · {{ number_format($accessStats['users_inactive']) }} nonaktif</small>
            </article>
            <article class="umkm-access-stat-card">
                <span>Google Linked</span>
                <strong>{{ number_format($accessStats['users_google_linked']) }}</strong>
                <small>{{ number_format($accessStats['users_google_required']) }} wajib Google</small>
            </article>
            <article class="umkm-access-stat-card">
                <span>Permission</span>
                <strong>{{ number_format($accessStats['permissions_total']) }}</strong>
                <small>{{ number_format($accessStats['role_permissions']) }} role-permission</small>
            </article>
            <article class="umkm-access-stat-card">
                <span>Audit & Security</span>
                <strong>{{ number_format($accessStats['security_events']) }}</strong>
                <small>{{ number_format($accessStats['audit_logs']) }} audit log</small>
            </article>
        </section>

        <section class="umkm-access-section-grid">
            @foreach ($accessSections as $section)
                <article class="umkm-access-section-card">
                    <div>
                        <span class="umkm-access-section-status">{{ $section['status'] }}</span>
                        <h3>{{ $section['title'] }}</h3>
                    </div>
                    <p>{{ $section['description'] }}</p>
                </article>
            @endforeach
        </section>

        <x-umkm.data-display.table-card>
            <div class="umkm-access-card-head">
                <div>
                    <span class="umkm-access-kicker">Akun</span>
                    <h3>Akun Pengguna</h3>
                    <p class="umkm-access-card-description">
                        Filter ini hanya membaca data minimal akun. Tidak ada aksi tambah, edit, delete, assign role, atau revoke session pada Access-1B.
                    </p>
                </div>
                <span class="umkm-access-soft-badge">Read-only detail</span>
            </div>

            <form method="GET" action="{{ route('admin-utama.access.index') }}" class="umkm-access-filter" autocomplete="off">
                <div>
                    <label for="filter-q">Cari akun</label>
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
                                $roleCodes = $account->roles->pluck('code')->filter()->values();
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
                                        Lihat
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="accountDetailModal{{ $account->id }}" tabindex="-1" aria-labelledby="accountDetailModalLabel{{ $account->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content umkm-access-detail-modal">
                                        <div class="modal-header">
                                            <div>
                                                <span class="umkm-access-kicker">Detail Akun Read-only</span>
                                                <h5 class="modal-title" id="accountDetailModalLabel{{ $account->id }}">{{ $account->name }}</h5>
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
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">Tidak ada akun yang sesuai filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="umkm-access-pagination">
                {{ $accounts->links() }}
            </div>
        </x-umkm.data-display.table-card>

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

        <x-umkm.data-display.table-card>
            <div class="umkm-access-card-head">
                <div>
                    <span class="umkm-access-kicker">Audit</span>
                    <h3>Aktivitas Akses Terbaru</h3>
                </div>
                <span class="umkm-access-soft-badge">Security trail</span>
            </div>

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

        <section class="umkm-access-security-note">
            <strong>Catatan batch Access-1B</strong>
            <span>
                Halaman ini memperluas daftar akun menjadi read-only detail dengan filter aman.
                Aksi tambah, edit, assign role, revoke session, dan perubahan permission tetap belum dibuka.
            </span>
        </section>
    </div>
@endsection