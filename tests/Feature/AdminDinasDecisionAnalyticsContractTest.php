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
        $view = file_get_contents(
            resource_path('views/pages/admin-dinas/analytics/decision.blade.php')
        );

        $this->assertIsString($service);
        $this->assertIsString($view);

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
        $this->assertStringContainsString('Indikasi potensi relatif', $view);
        $this->assertStringContainsString('Micro-Spatial Analytics', $view);
        $this->assertStringContainsString('bukan prediksi keberhasilan', $view);
        $this->assertStringContainsString(
            'Nilai sumber tetap dipertahankan apa adanya dan tidak dinormalisasi',
            $view
        );
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