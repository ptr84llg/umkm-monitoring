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
use Illuminate\Http\Request;

class AdminUtamaController extends Controller
{
    public function dashboard()
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
                'description' => 'Pengaturan sistem, keamanan, audit, dan kesiapan tata kelola.',
                'status' => 'Skeleton',
                'route_name' => null,
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

        $themeOptions = [
            ['key' => 'blue', 'label' => 'Blue', 'file' => 'umkm-theme-blue.css'],
            ['key' => 'green', 'label' => 'Green', 'file' => 'umkm-theme-green.css'],
            ['key' => 'maroon', 'label' => 'Maroon', 'file' => 'umkm-theme-maroon.css'],
            ['key' => 'gold', 'label' => 'Gold', 'file' => 'umkm-theme-gold.css'],
            ['key' => 'gradient-1', 'label' => 'Gradient 1', 'file' => 'umkm-theme-gradient-1.css'],
            ['key' => 'gradient-2', 'label' => 'Gradient 2', 'file' => 'umkm-theme-gradient-2.css'],
            ['key' => 'gradient-3', 'label' => 'Gradient 3', 'file' => 'umkm-theme-gradient-3.css'],
        ];

        $governanceNotes = [
            'Menu Admin Utama mengikuti role dan permission; UI bukan pengunci akhir.',
            'Backend guard tetap menjadi otoritas final untuk semua akses.',
            'Perubahan konfigurasi, konten, ekspor, dan akses sensitif wajib diaudit.',
            'Theme switching belum diaktifkan pada tahap ini dan akan menjadi batch terpisah.',
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

    public function settings()
    {
        return view('pages.admin-utama.governance.settings');
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