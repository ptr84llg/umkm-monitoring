<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PelakuBaselineDecisionAnalyticsContractTest extends TestCase
{
    public function test_baseline_decision_analytics_route_is_verified_read_only_workspace_route(): void
    {
        $this->assertTrue(Route::has('pelaku-umkm.analytics.index'));

        $route = Route::getRoutes()->getByName('pelaku-umkm.analytics.index');
        $this->assertNotNull($route);
        $this->assertSame('pelaku-umkm/analytics', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertEmpty(array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']));

        $middleware = $route->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('single.device', $middleware);
        $this->assertContains('role:pelaku_umkm', $middleware);
        $this->assertContains('permission:umkm.workspace.access', $middleware);
        $this->assertContains('pelaku.workspace.verified', $middleware);
    }

    public function test_analytics_contract_stays_inside_year_one_baseline_scope(): void
    {
        $service = file_get_contents(
            app_path('Services/PelakuUmkm/PelakuBaselineDecisionAnalyticsService.php')
        );
        $controller = file_get_contents(
            app_path('Http/Controllers/PelakuUmkm/PelakuAnalyticsController.php')
        );
        $view = file_get_contents(
            resource_path('views/pages/pelaku-umkm/analytics/index.blade.php')
        );

        $this->assertIsString($service);
        $this->assertIsString($controller);
        $this->assertIsString($view);

        $this->assertStringContainsString("'scope' => 'baseline_cross_sectional_spatial'", $service);
        $this->assertStringContainsString("'longitudinal_analysis' => false", $service);
        $this->assertStringContainsString("'forecasting' => false", $service);
        $this->assertStringContainsString("'automatic_recommendation' => false", $service);
        $this->assertStringContainsString("->where('c.is_primary', 1)", $service);
        $this->assertStringContainsString('minimum_group_size', $service);
        $this->assertStringContainsString('quantile(', $service);
        $this->assertStringContainsString('median(', $service);
        $this->assertStringContainsString('source_values_preserved', $service);
        $this->assertStringContainsString("'anomalies_excluded' => false", $service);

        $this->assertStringNotContainsString('umkm_performance_records', $service);
        $this->assertStringNotContainsString('monitoring_periods', $service);
        $this->assertStringNotContainsString('->insert(', $service);
        $this->assertStringNotContainsString('->update(', $service);
        $this->assertStringNotContainsString('->delete(', $service);
        $this->assertStringNotContainsString('->save(', $service);

        $this->assertStringNotContainsString('Tahun Pertama', $view);
        $this->assertStringContainsString('Berdasarkan data UMKM yang tersedia saat ini', $view);
        $this->assertStringContainsString('Kondisi yang perlu ditinjau', $view);
        $this->assertStringContainsString('Modal (Total / Nilai Tengah)', $view);
        $this->assertStringContainsString('role="progressbar"', $view);
        $this->assertStringContainsString('bukan prediksi keberhasilan usaha', $view);
        $this->assertStringContainsString('Nilai keuangan kelompok', $view);
        $this->assertStringContainsString('Nilai yang tercatat tetap dipertahankan apa adanya', $view);
    }

    public function test_peer_financial_analytics_is_aggregate_only_and_periodic_reporting_remains_deferred(): void
    {
        $service = file_get_contents(
            app_path('Services/PelakuUmkm/PelakuBaselineDecisionAnalyticsService.php')
        );
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertIsString($service);
        $this->assertIsString($routes);

        foreach (['capital_amount', 'annual_sales_amount', 'baseline_monthly_revenue'] as $sourceField) {
            $this->assertStringContainsString($sourceField, $service);
        }

        $this->assertStringContainsString("'total' =>", $service);
        $this->assertStringContainsString("'median' =>", $service);
        $this->assertStringContainsString("'privacy_suppressed' =>", $service);
        $this->assertStringContainsString('qualityAffectedIds', $service);

        foreach ([
            'pelaku-umkm.performance',
            'pelaku-umkm.performance.store',
            'pelaku-umkm.reporting',
            'pelaku-umkm.reporting.store',
        ] as $deferred) {
            $this->assertFalse(Route::has($deferred));
        }

        $this->assertStringNotContainsString("Route::post('/analytics", $routes);
    }
}