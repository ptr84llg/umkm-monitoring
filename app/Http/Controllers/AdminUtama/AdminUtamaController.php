<?php

namespace App\Http\Controllers\AdminUtama;

use App\Actions\AdminUtama\SanitizeNarrativeContent;
use App\Http\Controllers\Controller;
use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Audit\ApiRequestLog;
use App\Models\Audit\AuditLog;
use App\Models\Audit\SecurityEventLog;
use App\Models\Reference\BusinessCategoryReference;
use App\Models\Reference\BusinessTypeReference;
use App\Models\Reference\Region;
use App\Models\User;
use App\Services\AdminUtama\AdminAuditService;
use App\Services\System\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminUtamaController extends Controller
{
    public function dashboard(ThemeService $themeService)
    {
        $data = [
            'users' => User::query()->count(),
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
            'security_events' => SecurityEventLog::query()->count(),
        ];

        $systemSnapshot = [
            'api_logs' => ApiRequestLog::query()->count(),
            'audit_logs' => AuditLog::query()->count(),
            'business_category_references' => BusinessCategoryReference::query()->count(),
            'business_type_references' => BusinessTypeReference::query()->count(),
            'regions' => Region::query()->count(),
        ];

        $menuGroups = [
            [
                'key' => 'dashboard',
                'title' => 'Dashboard Kendali',
                'description' => 'Ringkasan kondisi sistem, keamanan, kualitas data, pengguna, dan layanan.',
                'status' => 'Aktif',
                'route_name' => 'admin-utama.dashboard',
                'permission' => 'dashboard.view.executive',
                'icon' => 'grid',
            ],
            [
                'key' => 'access',
                'title' => 'Akses',
                'description' => 'Akun, peran, izin akses, sesi, dan pembatasan akses pengguna.',
                'status' => 'Tersedia',
                'route_name' => 'admin-utama.access.index',
                'permission' => 'access.manage',
                'icon' => 'shield',
            ],
            [
                'key' => 'reference',
                'title' => 'Referensi',
                'description' => 'Wilayah, kategori usaha lokal, jenis usaha lokal, dan referensi pendukung data UMKM.',
                'status' => 'Belum tersedia',
                'route_name' => null,
                'permission' => 'reference.manage',
                'icon' => 'database',
            ],
            [
                'key' => 'governance',
                'title' => 'Tata Kelola',
                'description' => 'Pengaturan sistem, keamanan, riwayat perubahan, tampilan, dan tata kelola.',
                'status' => 'Tersedia',
                'route_name' => 'admin-utama.governance.settings',
                'permission' => 'system.manage',
                'icon' => 'settings',
            ],
            [
                'key' => 'publication',
                'title' => 'Publikasi',
                'description' => 'Pengumuman dan informasi publik yang sudah diperiksa.',
                'status' => 'Belum tersedia',
                'route_name' => null,
                'permission' => 'content.manage',
                'icon' => 'megaphone',
            ],
            [
                'key' => 'validation',
                'title' => 'Validasi',
                'description' => 'Formulir penilaian, pemeriksaan ahli, dan hasil penilaian.',
                'status' => 'Belum tersedia',
                'route_name' => null,
                'permission' => 'validation.manage',
                'icon' => 'check',
            ],
        ];

        $themeOptions = $themeService->options();

        $governanceNotes = [
            'Menu Admin Utama ditampilkan sesuai peran dan kewenangan pengguna.',
            'Setiap akses tetap diperiksa oleh sistem.',
            'Perubahan pengaturan, konten, ekspor, dan akses penting dicatat dalam riwayat sistem.',
            'Pilihan tampilan dapat diatur melalui Tata Kelola / Pengaturan Sistem.',
        ];

        return view('pages.admin-utama.dashboard', compact(
            'data',
            'systemSnapshot',
            'menuGroups',
            'themeOptions',
            'governanceNotes'
        ));
    }


    public function accessIndex(Request $request)
    {
        $rolePermissionCount = Schema::hasTable('role_permissions')
            ? DB::table('role_permissions')->count()
            : 0;

        $userRoleCount = Schema::hasTable('user_roles')
            ? DB::table('user_roles')->count()
            : 0;

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', 'all'),
            'role' => (string) $request->query('role', 'all'),
            'auth' => (string) $request->query('auth', 'all'),
        ];

        if (! in_array($filters['status'], ['all', 'active', 'inactive'], true)) {
            $filters['status'] = 'all';
        }

        if (! in_array($filters['auth'], ['all', 'google_linked', 'manual_only', 'google_required'], true)) {
            $filters['auth'] = 'all';
        }

        $roleOptions = Role::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'description', 'is_active']);

        if ($filters['role'] !== 'all' && ! $roleOptions->contains('code', $filters['role'])) {
            $filters['role'] = 'all';
        }

        $accountsQuery = User::query()
            ->with(['roles' => fn ($query) => $query->select('roles.id', 'roles.code', 'roles.name')])
            ->select([
                'id',
                'name',
                'email',
                'username',
                'auth_provider_required',
                'manual_login_disabled_at',
                'google_linked_at',
                'is_active',
                'last_login_at',
                'created_at',
            ])
            ->latest('id');

        if ($filters['q'] !== '') {
            $keyword = $filters['q'];

            $accountsQuery->where(function ($query) use ($keyword): void {
                $query->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('username', 'like', '%' . $keyword . '%');
            });
        }

        if ($filters['status'] === 'active') {
            $accountsQuery->where('is_active', true);
        } elseif ($filters['status'] === 'inactive') {
            $accountsQuery->where('is_active', false);
        }

        if ($filters['role'] !== 'all') {
            $accountsQuery->whereHas('roles', fn ($query) => $query->where('roles.code', $filters['role']));
        }

        if ($filters['auth'] === 'google_linked') {
            $accountsQuery->whereNotNull('google_linked_at');
        } elseif ($filters['auth'] === 'manual_only') {
            $accountsQuery->whereNull('google_linked_at')
                ->whereNull('manual_login_disabled_at');
        } elseif ($filters['auth'] === 'google_required') {
            $accountsQuery->where('auth_provider_required', User::AUTH_PROVIDER_GOOGLE)
                ->whereNotNull('manual_login_disabled_at');
        }

        $accounts = $accountsQuery
            ->paginate(12)
            ->withQueryString();

        $accessStats = [
            'users_total' => User::query()->count(),
            'users_active' => User::query()->where('is_active', true)->count(),
            'users_inactive' => User::query()->where('is_active', false)->count(),
            'users_google_linked' => User::query()->whereNotNull('google_linked_at')->count(),
            'users_google_required' => User::query()
                ->where('auth_provider_required', User::AUTH_PROVIDER_GOOGLE)
                ->whereNotNull('manual_login_disabled_at')
                ->count(),
            'roles_total' => Role::query()->count(),
            'roles_active' => Role::query()->where('is_active', true)->count(),
            'permissions_total' => Permission::query()->count(),
            'role_permissions' => $rolePermissionCount,
            'user_roles' => $userRoleCount,
            'security_events' => SecurityEventLog::query()->count(),
            'audit_logs' => AuditLog::query()->count(),
        ];

        $roleSummary = $roleOptions
            ->loadCount('permissions');

        $permissionModules = Permission::query()
            ->select('module', DB::raw('COUNT(*) as total'))
            ->groupBy('module')
            ->orderBy('module')
            ->get();

        $recentSecurityEvents = SecurityEventLog::query()
            ->latest('event_time')
            ->limit(6)
            ->get(['id', 'event_type', 'severity', 'event_time']);

        $recentAuditLogs = AuditLog::query()
            ->latest('event_time')
            ->limit(6)
            ->get(['id', 'action', 'target_type', 'target_id', 'event_time']);

        $accessSections = [
            [
                'key' => 'accounts',
                'title' => 'Akun Pengguna',
                'status' => 'Lihat saja',
                'description' => 'Melihat daftar akun, peran yang terhubung, status aktif, akun Google, dan waktu masuk terakhir tanpa mengubah data.',
            ],
            [
                'key' => 'roles',
                'title' => 'Peran',
                'status' => 'Lihat saja',
                'description' => 'Melihat peran pengguna dan jumlah izin akses yang terhubung.',
            ],
            [
                'key' => 'permissions',
                'title' => 'Izin Akses',
                'status' => 'Lihat saja',
                'description' => 'Melihat izin akses berdasarkan bagian sistem.',
            ],
            [
                'key' => 'assignment',
                'title' => 'Penetapan Akses',
                'status' => 'Belum tersedia',
                'description' => 'Penetapan peran dan izin akses belum tersedia. Saat ini sistem hanya menampilkan keterkaitan akun dan peran.',
            ],
            [
                'key' => 'sessions',
                'title' => 'Sesi & Perangkat',
                'status' => 'Belum tersedia',
                'description' => 'Pemantauan sesi, perangkat, penutupan sesi, dan riwayat masuk belum tersedia.',
            ],
            [
                'key' => 'audit',
                'title' => 'Riwayat Akses',
                'status' => 'Ringkasan',
                'description' => 'Melihat riwayat keamanan dan perubahan akses.',
            ],
        ];

        return view('pages.admin-utama.access.index', [
            'assetModules' => ['accessManager'],
            'accessStats' => $accessStats,
            'accounts' => $accounts,
            'roleOptions' => $roleOptions,
            'roleSummary' => $roleSummary,
            'permissionModules' => $permissionModules,
            'recentSecurityEvents' => $recentSecurityEvents,
            'recentAuditLogs' => $recentAuditLogs,
            'accessSections' => $accessSections,
            'filters' => $filters,
        ]);
    }
    public function accounts()
    {
        return view('pages.admin-utama.access.accounts', [
            'accounts' => User::query()
                ->latest()
                ->limit(25)
                ->get(['id', 'name', 'email', 'account_type', 'is_active']),
        ]);
    }

    public function roles()
    {
        return view('pages.admin-utama.access.roles', [
            'roles' => Role::query()
                ->withCount('permissions')
                ->get(),
        ]);
    }

    public function permissions()
    {
        return view('pages.admin-utama.access.permissions', [
            'permissions' => Permission::query()
                ->orderBy('module')
                ->get(),
        ]);
    }

    public function settings(ThemeService $themeService)
    {
        $themeOptions = $themeService->options();
        $activeThemeKey = $themeService->activeKey();
        $activeThemeLabel = collect($themeOptions)->firstWhere('key', $activeThemeKey)['label'] ?? 'Green';

        return view('pages.admin-utama.governance.settings', [
            'assetModules' => ['themeManager'],
            'themeOptions' => $themeOptions,
            'activeThemeKey' => $activeThemeKey,
            'activeThemeLabel' => $activeThemeLabel,
        ]);
    }

    public function updateTheme(
        Request $request,
        ThemeService $themeService,
        AdminAuditService $audit
    ) {
        $payload = $request->validate([
            'theme_key' => [
                'required',
                'string',
                Rule::in($themeService->allowedKeys()),
            ],
        ]);

        $before = $themeService->activeKey();
        $result = $themeService->setActiveTheme($payload['theme_key'], $request->user());

        $audit->logManagementChange(
            $request,
            'system.theme.update',
            'system_setting',
            null,
            ['active_theme' => $before],
            ['active_theme' => $result['after']]
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Tampilan sistem berhasil diperbarui.',
                'theme_key' => $result['after'],
            ]);
        }

        return back()->with('status', 'Tampilan sistem berhasil diperbarui.');
    }

    public function regions()
    {
        return view('pages.admin-utama.master-data.regions', [
            'regions' => Region::query()
                ->latest('id')
                ->limit(50)
                ->get(['id', 'code', 'name', 'level', 'parent_code', 'is_active']),
        ]);
    }

    public function surveySettings()
    {
        return view('pages.admin-utama.validation.survey-settings');
    }

    public function expertInstrumentSettings()
    {
        return view('pages.admin-utama.validation.expert-settings');
    }

    public function announcements()
    {
        return view('pages.admin-utama.publication.announcements');
    }

    public function securityLogs()
    {
        return view('pages.admin-utama.governance.security-logs', [
            'securityEvents' => SecurityEventLog::query()
                ->latest()
                ->limit(50)
                ->get(['id', 'event_type', 'severity', 'event_time']),
            'apiLogs' => ApiRequestLog::query()
                ->latest()
                ->limit(50)
                ->get(['id', 'method', 'endpoint', 'http_status', 'requested_at']),
        ]);
    }

    public function storeAnnouncement(
        Request $request,
        SanitizeNarrativeContent $sanitizeNarrativeContent,
        AdminAuditService $audit
    ) {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string'],
        ]);

        $sanitized = $sanitizeNarrativeContent->execute($payload['content']);

        $audit->logManagementChange(
            $request,
            'announcement.update',
            'announcement',
            null,
            [],
            [
                'title' => $payload['title'],
                'content' => $sanitized,
            ]
        );

        return back()->with('status', 'Konten berhasil diperiksa dan dicatat dalam riwayat sistem.');
    }
}
