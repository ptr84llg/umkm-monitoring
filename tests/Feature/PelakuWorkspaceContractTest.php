<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PelakuWorkspaceContractTest extends TestCase
{
    public function test_workspace_routes_are_read_only_and_verified_binding_guarded(): void
    {
        foreach ([
            'pelaku-umkm.dashboard',
            'pelaku-umkm.umkm.index',
            'pelaku-umkm.umkm.show',
        ] as $name) {
            $this->assertTrue(Route::has($name));
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('GET', $route->methods());
            $this->assertEmpty(array_intersect(
                $route->methods(),
                ['POST', 'PUT', 'PATCH', 'DELETE']
            ));
            $this->assertContains('pelaku.workspace.verified', $route->gatherMiddleware());
        }
    }

    public function test_legacy_proposal_and_profile_write_routes_remain_disabled(): void
    {
        foreach ([
            'pelaku-umkm.proposals.status',
            'pelaku-umkm.proposals.fix',
            'pelaku-umkm.proposals.fix.submit',
            'pelaku-umkm.survey',
        ] as $name) {
            $this->assertFalse(Route::has($name));
        }
    }

    public function test_workspace_controller_has_no_legacy_proposal_or_profile_write_flow(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/PelakuUmkm/PelakuUmkmController.php')
        );

        $this->assertIsString($controller);

        foreach ([
            'UmkmUpdateSubmission',
            'proposed_quality_status',
            'proposalFixSubmit',
            "->update([",
            "'quality_status' =>",
            "'business_name' =>",
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $controller);
        }
    }

    public function test_manual_and_google_login_use_workspace_permission_without_legacy_prefixes(): void
    {
        foreach ([
            app_path('Http/Controllers/Auth/LoginController.php'),
            app_path('Http/Controllers/Auth/GoogleOAuthController.php'),
        ] as $path) {
            $source = file_get_contents($path);
            $this->assertIsString($source);
            $this->assertStringContainsString("'permission' => 'umkm.workspace.access'", $source);
            $this->assertStringContainsString("'prefixes' => ['/pelaku-umkm']", $source);
        }
    }

    public function test_policy_does_not_grant_pelaku_direct_profile_update(): void
    {
        $policy = file_get_contents(app_path('Policies/Umkm/UmkmPolicy.php'));
        $this->assertIsString($policy);
        $this->assertStringContainsString("return \$user->hasPermission('umkm.write.official');", $policy);
        $this->assertStringContainsString('activeVerified()', $policy);
    }
}