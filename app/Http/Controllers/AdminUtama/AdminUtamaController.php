<?php

namespace App\Http\Controllers\AdminUtama;

use App\Actions\AdminUtama\SanitizeNarrativeContent;
use App\Http\Controllers\Controller;
use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Audit\ApiRequestLog;
use App\Models\Audit\AuditLog;
use App\Models\Audit\SecurityEventLog;
use App\Models\Reference\KbliReference;
use App\Models\Reference\RegionReference;
use App\Models\User;
use App\Services\AdminUtama\AdminAuditService;
use App\Services\System\ThemeService;
use Illuminate\Http\Request;
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
            'kbli_references' => KbliReference::query()->count(),
            'region_references' => RegionReference::query()->count(),
        ];

        $menuGroups = [
            [
                'key' => 'dashboard',
                'title' => 'Dashboard Kendali',
                'description' => 'Ringkasan sistem, keamanan, konfigurasi, kualitas data, user, dan modul.',
                'status' => 'Aktif',
                'route_name' => 'admin-utama.dashboard',
                'permission' => 'dashboard.view.executive',
                'icon' => 'grid',
            ],
            [
                'key' => 'access',
                'title' => 'Akses',
                'description' => 'Akun, role, permission, sesi, dan pembatasan akses pengguna.',
                'status' => 'Skeleton',
                'route_name' => null,
                'permission' => 'access.manage',
                'icon' => 'shield',
            ],
            [
                'key' => 'reference',
                'title' => 'Referensi',
                'description' => 'Wilayah, KBLI, kategori, dan referensi pendukung data UMKM.',
                'status' => 'Skeleton',
                'route_name' => null,
                'permission' => 'reference.manage',
                'icon' => 'database',
            ],
            [
                'key' => 'governance',
                'title' => 'Governance',
                'description' => 'Pengaturan sistem, keamanan, audit, theme, dan kesiapan tata kelola.',
                'status' => 'Theme Aktif',
                'route_name' => 'admin-utama.governance.settings',
                'permission' => 'system.manage',
                'icon' => 'settings',
            ],
            [
                'key' => 'publication',
                'title' => 'Publikasi',
                'description' => 'Pengumuman, narasi sistem, dan konten publik yang tersanitasi.',
                'status' => 'Skeleton',
                'route_name' => null,
                'permission' => 'content.manage',
                'icon' => 'megaphone',
            ],
            [
                'key' => 'validation',
                'title' => 'Validasi',
                'description' => 'Instrumen survei, validasi ahli, dan hasil penilaian terkontrol.',
                'status' => 'Skeleton',
                'route_name' => null,
                'permission' => 'validation.manage',
                'icon' => 'check',
            ],
        ];

        $themeOptions = $themeService->options();

        $governanceNotes = [
            'Menu Admin Utama mengikuti role dan permission; UI bukan pengunci akhir.',
            'Backend guard tetap menjadi otoritas final untuk semua akses.',
            'Perubahan konfigurasi, konten, ekspor, dan akses sensitif wajib diaudit.',
            'Theme management aktif melalui Governance / Pengaturan Sistem dengan allowlist backend.',
        ];

        return view('pages.admin-utama.dashboard', compact(
            'data',
            'systemSnapshot',
            'menuGroups',
            'themeOptions',
            'governanceNotes'
        ));
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

        return back()->with('status', 'Theme sistem berhasil diperbarui.');
    }

    public function kbliReferences()
    {
        return view('pages.admin-utama.master-data.kbli', [
            'kbli' => KbliReference::query()
                ->latest()
                ->limit(50)
                ->get(['id', 'kbli_code', 'title', 'is_active']),
        ]);
    }

    public function regions()
    {
        return view('pages.admin-utama.master-data.regions', [
            'regions' => RegionReference::query()
                ->latest()
                ->limit(50)
                ->get(['id', 'region_code', 'region_name', 'region_level', 'is_active']),
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

        return back()->with('status', 'Konten berhasil disanitasi dan dicatat audit.');
    }
}