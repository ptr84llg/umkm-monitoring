<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminDinasProfileReviewContractTest extends TestCase
{
    public function test_dedicated_profile_review_routes_are_guarded_and_legacy_routes_stay_off(): void
    {
        foreach ([
            'admin-dinas.profile-reviews.index' => 'GET',
            'admin-dinas.profile-reviews.show' => 'GET',
            'admin-dinas.profile-reviews.review' => 'POST',
        ] as $name => $method) {
            $this->assertTrue(Route::has($name));
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains($method, $route->methods());
            $this->assertContains('role:admin_dinas', $route->gatherMiddleware());
            $this->assertContains('permission:umkm.profile.review', $route->gatherMiddleware());
        }

        foreach ([
            'admin-dinas.proposals.index',
            'admin-dinas.proposals.show',
            'admin-dinas.proposals.review',
        ] as $legacy) {
            $this->assertFalse(Route::has($legacy));
        }
    }

    public function test_review_workflow_is_single_decision_cumulative_and_source_immutable(): void
    {
        $proposalService = file_get_contents(app_path('Services/Proposal/UmkmProposalService.php'));
        $approvedService = file_get_contents(app_path('Services/PelakuUmkm/ApprovedProfileOverrideService.php'));

        $this->assertIsString($proposalService);
        $this->assertIsString($approvedService);
        $this->assertStringContainsString('lockForUpdate()', $proposalService);
        $this->assertStringContainsString("\$locked->status_data !== 'diajukan'", $proposalService);
        $this->assertStringContainsString('array_replace($currentOverlay, $changes)', $approvedService);
        $this->assertStringContainsString('conflictingFields', $approvedService);
        $this->assertStringContainsString('lockForUpdate()', $approvedService);
        $this->assertStringNotContainsString('->update([', $approvedService);
        $this->assertStringNotContainsString("'business_name' =>", $approvedService);
        $this->assertStringNotContainsString("'quality_status' =>", $approvedService);
    }
}