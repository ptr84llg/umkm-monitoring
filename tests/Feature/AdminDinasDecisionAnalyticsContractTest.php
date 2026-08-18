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

        $this->assertStringContainsString('Perbandingan & Potensi UMKM', $view);
        $this->assertStringContainsString('Jika memilih <strong>Semua</strong>, sistem menampilkan ringkasan tingkat kota', $view);
        $this->assertStringContainsString('Jenis Usaha dengan Jumlah Terbesar', $view);
        $this->assertStringContainsString('Jenis Usaha × Kecamatan', $view);
        $this->assertStringContainsString('Pasangan Jenis Usaha–Kecamatan yang Perlu Ditinjau', $view);
        $this->assertStringContainsString('Batang perbandingan membantu melihat perbedaan jumlah', $view);
        $this->assertStringNotContainsString('Pilih <strong>Jenis Usaha</strong> untuk membandingkan', $view);
        $this->assertStringContainsString('Kondisi yang perlu ditinjau', $view);
        $this->assertStringContainsString('Informasi Lokasi Pendukung', $view);
        $this->assertStringContainsString('hanya digunakan sebagai pelengkap dan tidak menentukan hasil perbandingan', $view);
        $this->assertStringContainsString('bukan jaminan keberhasilan', $view);
        $this->assertStringContainsString(
            'Nilai yang tercatat tetap dipertahankan apa adanya',
            $view
        );

        foreach (['radius_meters', 'Radius Spasial', 'Radius aktif', 'Jumlah usaha Radius Aktif'] as $removedRadiusContract) {
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
        $this->assertStringContainsString("'label' => 'Informasi & Perbandingan'", $layout);
        $this->assertStringContainsString("'title' => 'Perbandingan & Potensi'", $layout);
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
