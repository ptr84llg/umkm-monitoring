<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminDinasDecisionAnalyticsContractTest extends TestCase
{
    public function test_decision_analytics_route_is_read_only_and_guarded(): void
    {
        $this->assertTrue(Route::has('admin-dinas.analytics.decision'));

        $route = Route::getRoutes()->getByName('admin-dinas.analytics.decision');

        $this->assertNotNull($route);
        $this->assertSame('admin-dinas/analytics/decision', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertEmpty(array_intersect(
            $route->methods(),
            ['POST', 'PUT', 'PATCH', 'DELETE']
        ));

        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains('single.device', $middleware);
        $this->assertContains('role:admin_dinas', $middleware);
        $this->assertContains('permission:umkm.read.official', $middleware);
    }

    public function test_decision_analytics_contract_preserves_year_one_source_and_interpretation_boundaries(): void
    {
        $service = file_get_contents(
            app_path('Services/AdminDinas/AdminDinasDecisionAnalyticsService.php')
        );
        $controller = file_get_contents(
            app_path('Http/Controllers/AdminDinas/AdminDinasController.php')
        );
        $view = file_get_contents(
            resource_path('views/pages/admin-dinas/analytics/decision.blade.php')
        );
        $shellCss = file_get_contents(
            public_path('assets/css/core/layout/umkm-internal-shell.css')
        );

        $this->assertIsString($service);
        $this->assertIsString($controller);
        $this->assertIsString($view);
        $this->assertIsString($shellCss);

        $this->assertStringContainsString(
            "'scope' => 'year_1_baseline_cross_sectional_spatial_decision_support'",
            $service
        );
        $this->assertStringContainsString("'source_values_preserved' => true", $service);
        $this->assertStringContainsString("'anomalies_excluded' => false", $service);
        $this->assertStringContainsString("'longitudinal_analysis' => false", $service);
        $this->assertStringContainsString("'forecasting' => false", $service);
        $this->assertStringContainsString("'causal_inference' => false", $service);
        $this->assertStringContainsString("'automatic_recommendation' => false", $service);
        $this->assertStringContainsString("->where('c.is_primary', 1)", $service);
        $this->assertStringContainsString('haversineMeters(', $service);
        $this->assertStringContainsString('quantile(', $service);
        $this->assertStringContainsString('median(', $service);
        $this->assertStringContainsString('citywideDecisionOverview(', $service);
        $this->assertStringContainsString("'analysis_mode' => \$analysisMode", $service);
        $this->assertStringContainsString("'potential_pairs' => \$potentialPairs->all()", $service);
        $this->assertStringContainsString("'distribution_matrix' => [", $service);
        $this->assertStringContainsString(
            "'economic_metric_rule' => 'highest_available_numeric_coverage_with_minimum_group_size_tie_preserves_metric_order'",
            $service
        );

        foreach ([
            '->insert(',
            '->update(',
            '->delete(',
            '->save(',
            'umkm_performance_records',
            'monitoring_periods',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $service);
        }

        $this->assertStringContainsString('Analitik Keputusan Admin Dinas', $view);
        $this->assertStringContainsString('Opsi <strong>Semua</strong> tetap menghasilkan analitik keputusan tingkat kota', $view);
        $this->assertStringContainsString('Jenis Usaha dengan Jumlah Terbesar', $view);
        $this->assertStringContainsString('Jenis Usaha × Kecamatan', $view);
        $this->assertStringContainsString('Pasangan Jenis Usaha–Kecamatan yang Perlu Ditinjau', $view);
        $this->assertStringContainsString('Visual bar hanya membantu membandingkan besaran', $view);
        $this->assertStringNotContainsString('Pilih <strong>Jenis Usaha</strong> untuk membandingkan', $view);
        $this->assertStringContainsString('Indikasi potensi relatif', $view);
        $this->assertStringContainsString('Informasi Spasial Pendukung', $view);
        $this->assertStringContainsString('tidak digunakan sebagai filter atau penentu label Analitik Keputusan', $view);
        $this->assertStringContainsString('bukan prediksi keberhasilan', $view);
        $this->assertStringContainsString(
            'Nilai sumber tetap dipertahankan apa adanya dan tidak dinormalisasi',
            $view
        );

        foreach (['radius_meters', 'Radius Spasial', 'Radius aktif', 'Kepadatan Radius Aktif'] as $removedRadiusContract) {
            $this->assertStringNotContainsString($removedRadiusContract, $view);
        }

        $this->assertStringNotContainsString('radius_meters', $controller);
        $this->assertStringNotContainsString('radius_meters', $service);
        $this->assertStringNotContainsString('RADIUS_OPTIONS', $service);
        $this->assertStringNotContainsString('neighbors_selected_radius', $service);
        $this->assertStringContainsString(
            "'micro_spatial_rule' => 'same_primary_type_haversine_context_only_not_decision_filter'",
            $service
        );

        $this->assertStringContainsString(
            'max-height: calc(100dvh - var(--dashboard-topbar-height) - 2rem);',
            $shellCss
        );
        $this->assertStringContainsString('overflow-y: auto;', $shellCss);
        $this->assertStringContainsString('overscroll-behavior: contain;', $shellCss);
    }

    public function test_admin_dinas_menu_separates_operational_and_decision_analytics_sections(): void
    {
        $layout = file_get_contents(
            resource_path('views/layouts/dashboard.blade.php')
        );

        $this->assertIsString($layout);
        $this->assertStringContainsString("'label' => 'Operasional'", $layout);
        $this->assertStringContainsString("'label' => 'Analitik & Keputusan'", $layout);
        $this->assertStringContainsString("'title' => 'Analitik Keputusan'", $layout);
        $this->assertStringContainsString(
            "'route' => 'admin-dinas.analytics.decision'",
            $layout
        );
        $this->assertStringContainsString(
            "'permission' => 'umkm.read.official'",
            $layout
        );
        $this->assertGreaterThanOrEqual(
            2,
            substr_count(
                $layout,
                '$menuDisplay = $dashboardMenuDisplay($menuItem);'
            )
        );
    }
}
