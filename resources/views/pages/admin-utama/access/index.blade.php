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
                    dan audit akses. Pada batch awal, halaman ini dibuat sebagai fondasi read-only agar struktur
                    akses dapat terlihat jelas tanpa membuka aksi sensitif terlebih dahulu.
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
                <span>Role</span>
                <strong>{{ number_format($accessStats['roles_total']) }}</strong>
                <small>{{ number_format($accessStats['roles_active']) }} role aktif</small>
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

        <div class="umkm-access-grid-two">
            <x-umkm.data-display.table-card>
                <div class="umkm-access-card-head">
                    <div>
                        <span class="umkm-access-kicker">Akun</span>
                        <h3>Akun Terbaru</h3>
                    </div>
                    <span class="umkm-access-soft-badge">Read-only</span>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle umkm-access-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Google</th>
                                <th>Login Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentUsers as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="umkm-access-badge {{ $user->is_active ? 'is-success' : 'is-muted' }}">
                                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="umkm-access-badge {{ $user->google_linked_at ? 'is-info' : 'is-muted' }}">
                                            {{ $user->google_linked_at ? 'Linked' : 'Belum' }}
                                        </span>
                                    </td>
                                    <td>{{ $user->last_login_at?->format('d M Y H:i') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">Belum ada akun.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-umkm.data-display.table-card>

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
        </div>

        <div class="umkm-access-grid-two">
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
                                <small>{{ $event->severity }} · {{ $event->ip_address ?? '-' }} · {{ $event->event_time?->format('d M Y H:i') ?? '-' }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada security event.</p>
                    @endforelse
                </div>
            </x-umkm.data-display.table-card>
        </div>

        <section class="umkm-access-security-note">
            <strong>Catatan batch Access-1A</strong>
            <span>
                Halaman ini belum membuka aksi tambah, edit, assign role, revoke session, atau perubahan permission.
                Aksi sensitif tersebut harus dibuat pada batch berikutnya menggunakan modal konfirmasi, AJAX internal,
                backend validation, permission guard, dan audit log.
            </span>
        </section>
    </div>
@endsection