<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Checkpoint10FContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_and_reporting_lineage_schema_contract_is_present(): void
    {
        foreach (['user_devices', 'auth_device_sessions', 'auth_otp_challenges'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing required security table: {$table}");
        }

        foreach (['umkm_performance_record_revisions', 'umkm_current_performance_revisions'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing required reporting-lineage table: {$table}");
        }

        foreach ([
            'umkm_id',
            'monitoring_period_id',
            'previous_revision_id',
            'revision_no',
            'monthly_revenue',
            'worker_count',
            'production_volume',
            'status_data',
            'submitted_by_user_id',
            'submitted_at',
            'revision_reason',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('umkm_performance_record_revisions', $column), "Missing reporting-lineage column: {$column}");
        }
    }

    public function test_all_authenticated_internal_routes_receive_single_device_guard(): void
    {
        foreach ([
            'logout',
            'session.keep-alive',
            'admin-dinas.dashboard',
            'admin-dinas.account-claims.review',
            'admin-dinas.profile-reviews.review',
            'pelaku-umkm.dashboard',
            'pelaku-umkm.profile-change.store',
        ] as $name) {
            $this->assertTrue(Route::has($name), $name.' must exist.');
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('single.device', $route->gatherMiddleware(), $name.' must use single.device.');
        }
    }

    public function test_sensitive_write_routes_keep_throttle_and_safe_error_contract(): void
    {
        foreach ([
            'pelaku-claim.store',
            'pelaku-activation.activate',
            'login.store',
            'login.otp.verify',
            'login.otp.resend',
            'password.update',
            'password.otp.verify',
            'password.otp.resend',
            'admin-dinas.account-claims.invite.store',
            'admin-dinas.account-claims.review',
            'admin-dinas.account-claims.resend',
            'pelaku-umkm.profile-change.store',
            'admin-dinas.profile-reviews.review',
        ] as $name) {
            $this->assertTrue(Route::has($name), $name.' must exist.');
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('POST', $route->methods());
            $this->assertEmpty(array_intersect($route->methods(), ['PUT', 'PATCH', 'DELETE']));
            $middleware = $route->gatherMiddleware();
            $this->assertTrue(
                collect($middleware)->contains(fn ($item) => str_starts_with((string) $item, 'throttle:')),
                $name.' must be throttled.'
            );
            $this->assertContains('safe.errors', $middleware, $name.' must use safe.errors.');
        }
    }

    public function test_authentication_controllers_register_and_revoke_device_sessions(): void
    {
        $login = file_get_contents(app_path('Http/Controllers/Auth/LoginController.php'));
        $otp = file_get_contents(app_path('Http/Controllers/Auth/LoginOtpController.php'));
        $google = file_get_contents(app_path('Http/Controllers/Auth/GoogleOAuthController.php'));
        $reset = file_get_contents(app_path('Http/Controllers/Auth/PasswordResetController.php'));
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        foreach ([$login, $otp, $google, $reset, $bootstrap, $routes] as $source) {
            $this->assertIsString($source);
        }

        $this->assertStringContainsString("activate(\$user, \$request, 'manual'", $login);
        $this->assertStringContainsString("activate(\$user, \$request, 'manual_otp'", $otp);
        $this->assertStringContainsString("activate(\$user, \$request, 'google'", $google);
        $this->assertStringContainsString("revokeCurrentSession(\$request->user(), \$request, 'logout')", $login);
        $this->assertStringContainsString("revokeAllForUser(\$context['user']->fresh(), 'password_reset')", $reset);
        $this->assertStringContainsString("'single.device' =>", $bootstrap);
        $this->assertStringContainsString("Route::middleware(['auth', 'single.device'])->group", $routes);
    }

    public function test_source_mutation_guards_and_profile_system_field_prohibitions_remain_present(): void
    {
        $proposal = file_get_contents(app_path('Services/Proposal/UmkmProposalService.php'));
        $official = file_get_contents(app_path('Services/AdminDinas/UmkmOfficialService.php'));
        $request = file_get_contents(app_path('Http/Requests/PelakuUmkm/SubmitProfileOverrideRequest.php'));

        $this->assertIsString($proposal);
        $this->assertIsString($official);
        $this->assertIsString($request);
        $this->assertStringNotContainsString('umkm.official.update.by_proposal_approval', $proposal);
        $this->assertStringContainsString('assertNotSourceOwned', $official);

        foreach (['quality_status', 'status_data', 'source_system', 'source_record_id', 'source_active', 'source_snapshot', 'notes'] as $field) {
            $this->assertStringContainsString("'{$field}' => ['prohibited']", $request);
        }
    }

    public function test_periodic_reporting_routes_remain_deferred_while_lineage_foundation_is_ready(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $this->assertIsString($routes);
        $this->assertStringNotContainsString('UmkmPerformanceRevisionService', $routes);
        $this->assertStringNotContainsString('performance-reports', $routes);

        $service = file_get_contents(app_path('Services/Reporting/UmkmPerformanceRevisionService.php'));
        $revision = file_get_contents(app_path('Models/Umkm/UmkmPerformanceRecordRevision.php'));
        $this->assertIsString($service);
        $this->assertIsString($revision);
        $this->assertStringContainsString('previous_revision_id', $service);
        $this->assertStringContainsString('revision_reason', $service);
        $this->assertStringContainsString('append-only', $revision);
    }
}