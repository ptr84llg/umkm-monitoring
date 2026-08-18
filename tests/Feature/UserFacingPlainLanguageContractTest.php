<?php

namespace Tests\Feature;

use Tests\TestCase;

class UserFacingPlainLanguageContractTest extends TestCase
{
    public function test_public_and_user_interfaces_use_plain_language(): void
    {
        $uiPaths = [
            'resources/views/layouts/dashboard.blade.php',
            'resources/views/layouts/public.blade.php',
            'resources/views/partials/public/shared/public-footer.blade.php',
            'resources/views/layouts/auth.blade.php',
            'resources/views/layouts/expert-validation.blade.php',
            'resources/views/pages/dashboard/interactive.blade.php',

            'resources/views/pages/public/landing/index.blade.php',
            'resources/views/partials/public/landing/components/dashboard-preview.blade.php',
            'resources/views/partials/public/landing/components/hero-preview-board.blade.php',
            'resources/views/partials/public/landing/components/cta-section.blade.php',
            'resources/views/components/umkm/feedback/region-selector-modal.blade.php',
            'resources/views/components/umkm/feedback/location-gate-modal.blade.php',
            'public/assets/js/pages/public/landing/landing-analytics-insights.js',
            'public/assets/js/pages/public/landing/landing-analytics-domain-upgrade.js',
            'public/assets/js/pages/public/landing/landing-aggregate-cards.js',
            'public/assets/js/pages/public/landing/landing-region.js',
            'public/assets/js/pages/public/landing/landing-region-map-google.js',
            'public/assets/js/pages/public/landing/landing-chart.js',

            'resources/views/pages/auth/login.blade.php',
            'resources/views/pages/auth/forgot-password.blade.php',
            'resources/views/pages/auth/otp-challenge.blade.php',
            'resources/views/pages/auth/reset-password.blade.php',
            'resources/views/pages/auth/google-link-confirm.blade.php',
            'public/assets/js/pages/auth/login.js',

            'resources/views/pages/pelaku-umkm/dashboard.blade.php',
            'resources/views/pages/pelaku-umkm/analytics/index.blade.php',
            'resources/views/pages/pelaku-umkm/umkm-show.blade.php',
            'resources/views/pages/pelaku-umkm/profile-proposals-index.blade.php',
            'resources/views/pages/pelaku-umkm/profile-proposal-show.blade.php',
            'resources/views/pages/pelaku-umkm/profile-proposal-create.blade.php',
            'resources/views/pages/pelaku-umkm/account-claim/activate.blade.php',
            'resources/views/pages/pelaku-umkm/account-claim/create.blade.php',
            'resources/views/pages/pelaku-umkm/account-claim/status.blade.php',

            'resources/views/pages/admin-dinas/dashboard.blade.php',
            'resources/views/pages/admin-dinas/partials/active-context.blade.php',
            'resources/views/pages/admin-dinas/umkm-index.blade.php',
            'resources/views/pages/admin-dinas/umkm-show.blade.php',
            'resources/views/pages/admin-dinas/analytics/index.blade.php',
            'resources/views/pages/admin-dinas/analytics/decision.blade.php',
            'resources/views/pages/admin-dinas/analytics/financial.blade.php',
            'resources/views/pages/admin-dinas/analytics/spatial.blade.php',
            'resources/views/pages/admin-dinas/account-claims/index.blade.php',
            'resources/views/pages/admin-dinas/account-claims/invite.blade.php',
            'resources/views/pages/admin-dinas/account-claims/show.blade.php',
            'resources/views/pages/admin-dinas/profile-reviews-index.blade.php',
            'resources/views/pages/admin-dinas/profile-review-show.blade.php',

            'resources/views/pages/admin-utama/dashboard.blade.php',
            'resources/views/pages/admin-utama/access/index.blade.php',
            'resources/views/pages/admin-utama/governance/settings.blade.php',
            'resources/views/pages/admin-utama/governance/security-logs.blade.php',
            'resources/views/pages/admin-utama/master-data/regions.blade.php',
            'resources/views/pages/admin-utama/publication/announcements.blade.php',
            'resources/views/pages/admin-utama/validation/expert-settings.blade.php',
            'resources/views/pages/admin-utama/validation/survey-settings.blade.php',

            'resources/views/pages/kepala-dinas/dashboard.blade.php',
            'resources/views/pages/validasi-ahli/validator-form.blade.php',
        ];

        $content = collect($uiPaths)
            ->map(fn (string $path): string => (string) file_get_contents(base_path($path)))
            ->implode("\n");

        foreach ([
            'Visual Analitik Interaktif',
            'AJAX internal belum siap.',
            'Memuat agregat',
            'Menunggu sinkronisasi',
            'Pusat Analitik & Wawasan Interaktif',
            'Analitik Keputusan Pelaku UMKM',
            'rule pembanding baseline',
            'Metodologi & Batas Penggunaan',
            '<code>umkm.sensitive.coordinate</code>',
            'Internal layout core · backend guard tetap otoritas final',
            'Panel aktivitas internal disiapkan sebagai shell',
            'Privacy guard aktif',
            'Read-only',
            'RBAC',
            'PBAC',
            'Submit Final',
            'coordinate-mapped',
            'rule transparan',
            'rule potensi',
            'DB-backed',
            'Public-safe',
            'Kredensial',
            'kredensial',
            'Super Admin',
            'Theme Aktif',
            'Coming next',
            'Audit log',
            'API log',
            'Dashboard Visual Analitik Interaktif',
            'Login Internal | Monitoring UMKM',
            'Mengalihkan ke dashboard',
            'melalui AJAX',
            'Core form belum siap',
            'Data login Anda',
            'Login belum lengkap',
            'Login belum berhasil',
            'Akses login',
            'Status izin lokasi:',
            'Site settings',
            '<strong>Location</strong>',
            '<strong>Allow</strong>',
            '<strong>Ask</strong>',
            'Refresh halaman',
            'Kode OTP',
            'Buat Password',
            'Konfirmasi Password',
            'Verifikasi OTP',
            'Klaim Akun Pelaku UMKM',
            'Klaim Mandiri',
            'masukkan OTP',
            'Buka Analitik Keputusan',
            'Buka Analitik',
            'gunakan analitik',
            'Informasi sistem — tidak dapat diajukan untuk diubah',
            'ID Data Sumber',
            'ID data sumber',
            'Data sumber/LSS',
            'data sumber LSS',
            'Belum terasosiasi',
            'Nilai Sumber',
            'bukan validasi legal formal',
            'Konteks Aktif',
            ' record</span>',
            'Peta dan Analisis Wilayah',
            'izin koordinat sensitif',
            'status lokasi = terpetakan',
            'Versi data:',
            'Terakhir sinkron:',
            'Analitik Ekonomi & Keuangan',
            'tidak dinormalisasi',
            'dari konteks aktif',
            'Sumber Pinjaman Teridentifikasi',
            'Scope: baseline cross-sectional',
            'coverage numerik',
            'Agregat ekonomi minimum',
            'record terisi',
            'data read-only',
            'Informasi spasial belum tersedia',
            'kedekatan spasial memerlukan izin',
            'forecasting',
            'causal inference',
            'RBAC / PBAC',
            'Matriks Role & Permission',
            'Role Sistem',
            'Kelompok Permission',
            'session revoke',
            'assignment belum',
            'Security trail',
            'security event',
            'Access-1B',
            'AccessUI-BootstrapFirst-1A',
            'Bootstrap-first',
            'Google linked',
            'Manual only',
            'Belum linked',
            'Role terhubung',
            'security-logs',
            'expert-settings',
            'survey-settings',
            'WYSIWYG',
            'allowlist backend',
            'CSRF',
            'header internal',
            'role/permission',
            'Manajemen Theme Sistem',
            'Konfirmasi Theme',
            'Gunakan Theme',
            '<th>ID</th>',
        ] as $forbidden) {
            $this->assertFalse(
                str_contains($content, $forbidden),
                "User-facing technical/research phrase leaked: {$forbidden}"
            );
        }

        foreach ([
            'SISFODA UMKM | Informasi UMKM',
            'Informasi UMKM Kota Lubuk Linggau',
            'Perbandingan Usaha dan Kondisi Wilayah',
            'Perbandingan & Potensi UMKM',
            'Informasi belum dapat dimuat. Silakan coba lagi.',
            'Belum ada aktivitas terbaru yang perlu ditampilkan.',
            'Akses dan informasi disesuaikan dengan kewenangan pengguna.',
            'Informasi ditampilkan dalam bentuk ringkasan dan tidak menampilkan lokasi rinci masing-masing usaha.',
            'Informasi ditampilkan dalam bentuk ringkasan untuk melindungi data masing-masing usaha.',
            'Simpan Sementara',
            'Kirim Penilaian',
            'Kode Verifikasi',
            'Kata Sandi',
        ] as $required) {
            $this->assertTrue(
                str_contains($content, $required),
                "Plain-language replacement missing: {$required}"
            );
        }
    }

    public function test_dynamic_display_strings_are_plain_but_internal_rules_remain_unchanged(): void
    {
        $publicData = (string) file_get_contents(app_path('Support/PublicLanding/PublicLandingData.php'));
        $pelaku = (string) file_get_contents(app_path('Services/PelakuUmkm/PelakuBaselineDecisionAnalyticsService.php'));
        $admin = (string) file_get_contents(app_path('Services/AdminDinas/AdminDinasDecisionAnalyticsService.php'));
        $adminUtama = (string) file_get_contents(app_path('Http/Controllers/AdminUtama/AdminUtamaController.php'));
        $profile = (string) file_get_contents(app_path('Http/Controllers/PelakuUmkm/ProfileOverrideController.php'));

        foreach ([
            "'chip' => 'Data agregat'",
            "?? 'DB-backed'",
            "'public_safe_label' => 'Public-safe'",
            "'source_label' => 'Data agregat database'",
            "'total_context' => 'Public-safe'",
        ] as $legacy) {
            $this->assertFalse(str_contains($publicData, $legacy), "Legacy public label remains: {$legacy}");
        }

        foreach ([
            'Omzet bulanan baseline',
            'Kepadatan teridentifikasi; agregat ekonomi dibatasi',
            'Kepadatan rendah; data ekonomi belum cukup',
            'Kepadatan tinggi; data ekonomi belum cukup',
            'Indikasi potensi wilayah relatif',
            'Pasar aktif-kompetitif',
            'Indikasi tekanan persaingan relatif',
            'Data agregat belum cukup untuk indikasi potensi',
            'Data ekonomi belum cukup untuk interpretasi',
            'Indikasi potensi relatif',
            'Sama dengan median kelompok',
            'Di atas median kelompok',
            'Di bawah median kelompok',
        ] as $legacy) {
            $this->assertFalse(str_contains($pelaku, $legacy), "Legacy Pelaku display label remains: {$legacy}");
        }

        foreach ([
            "'title' => 'Gambaran Keputusan Seluruh Kota'",
            'Konsentrasi teridentifikasi; agregat ekonomi dibatasi',
            'Indikasi potensi wilayah relatif',
            'Pasar aktif-kompetitif',
            'Indikasi tekanan persaingan relatif',
            'Data agregat belum cukup untuk indikasi potensi',
            'Mutu data perlu diperhatikan',
            'Sebagian record dalam analisis memiliki flag mutu terbuka.',
        ] as $legacy) {
            $this->assertFalse(str_contains($admin, $legacy), "Legacy Admin Dinas display label remains: {$legacy}");
        }

        foreach ([
            'Backend guard tetap menjadi otoritas final untuk semua akses.',
            'Theme management aktif melalui Governance',
            "'status' => 'Read-only detail'",
            "'status' => 'Read-only matrix'",
            "'status' => 'Read-only grouping'",
            "'status' => 'Read-only preview'",
            "'status' => 'Coming next'",
        ] as $legacy) {
            $this->assertFalse(str_contains($adminUtama, $legacy), "Legacy Admin Utama display label remains: {$legacy}");
        }

        $this->assertStringNotContainsString(
            'Usulan perubahan profil berhasil diajukan tanpa mengubah data sumber.',
            $profile
        );

        foreach ([
            "'scope' => 'baseline_cross_sectional_spatial'",
            "'potential_rule' => 'lower_quartile_business_count_and_at_or_above_reference_median'",
            'quantile(',
            'median(',
            "'source_values_preserved' => true",
            "'anomalies_excluded' => false",
        ] as $rule) {
            $this->assertStringContainsString($rule, $pelaku, "Pelaku analytics rule changed: {$rule}");
        }

        foreach ([
            "'scope' => 'year_1_baseline_cross_sectional_spatial_decision_support'",
            "'economic_metric_rule' => 'highest_available_numeric_coverage_with_minimum_group_size_tie_preserves_metric_order'",
            'haversineMeters(',
            'quantile(',
            'median(',
            "'source_values_preserved' => true",
            "'anomalies_excluded' => false",
        ] as $rule) {
            $this->assertStringContainsString($rule, $admin, "Admin Dinas analytics rule changed: {$rule}");
        }

        foreach ([
            "'source_label' => 'Ringkasan data'",
            "'public_safe_label' => 'Aman untuk publik'",
        ] as $plain) {
            $this->assertTrue(str_contains($publicData, $plain), "Plain public label missing: {$plain}");
        }

        foreach ([
            'Jumlah usaha tersedia; data ekonomi kelompok dibatasi',
            'Kondisi wilayah perlu ditinjau',
            'Kondisi yang perlu ditinjau',
            'Sama dengan nilai tengah usaha sejenis',
        ] as $plain) {
            $this->assertTrue(str_contains($pelaku, $plain), "Plain Pelaku label missing: {$plain}");
        }

        $this->assertTrue(str_contains($admin, 'Ringkasan UMKM Seluruh Kota'));
        $this->assertTrue(str_contains($admin, 'Kualitas data perlu diperhatikan'));
        $this->assertTrue(str_contains($adminUtama, 'Setiap akses tetap diperiksa oleh sistem.'));
        $this->assertTrue(str_contains(
            $profile,
            'Usulan perubahan berhasil diajukan. Data awal usaha tetap tersimpan.'
        ));
    }
}
