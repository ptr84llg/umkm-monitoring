<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PelakuProfileOverrideContractTest extends TestCase
{
    public function test_checkpoint_10d_exposes_owned_profile_proposals_but_not_dinas_review(): void
    {
        foreach ([
            'pelaku-umkm.profile-change.create',
            'pelaku-umkm.profile-change.store',
            'pelaku-umkm.profile-proposals.index',
            'pelaku-umkm.profile-proposals.show',
        ] as $name) {
            $this->assertTrue(Route::has($name));
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('pelaku.workspace.verified', $route->gatherMiddleware());
        }

        $store = Route::getRoutes()->getByName('pelaku-umkm.profile-change.store');
        $this->assertContains('POST', $store->methods());
        $this->assertContains('permission:umkm.profile.propose', $store->gatherMiddleware());

        $this->assertFalse(Route::has('admin-dinas.proposals.index'));
        $this->assertFalse(Route::has('admin-dinas.proposals.review'));
    }

    public function test_active_request_prohibits_quality_and_provenance_fields(): void
    {
        $request = file_get_contents(
            app_path('Http/Requests/PelakuUmkm/SubmitProfileOverrideRequest.php')
        );
        $this->assertIsString($request);

        foreach ([
            'quality_status',
            'status_data',
            'source_system',
            'source_record_id',
            'source_active',
            'source_snapshot',
            'notes',
        ] as $field) {
            $this->assertStringContainsString("'{$field}' => ['prohibited']", $request);
        }
    }

    public function test_approval_service_writes_override_not_umkm_source(): void
    {
        $service = file_get_contents(app_path('Services/Proposal/UmkmProposalService.php'));
        $approved = file_get_contents(app_path('Services/PelakuUmkm/ApprovedProfileOverrideService.php'));
        $resolver = file_get_contents(app_path('Services/PelakuUmkm/EffectiveUmkmProfileService.php'));

        $this->assertIsString($service);
        $this->assertIsString($approved);
        $this->assertIsString($resolver);
        $this->assertStringNotContainsString('umkm.official.update.by_proposal_approval', $service);
        $this->assertStringNotContainsString("'quality_status' =>", $service);
        $this->assertStringContainsString('UmkmProfileOverrideRevision::query()->create', $approved);
        $this->assertStringContainsString('UmkmCurrentProfileOverride::query()->updateOrCreate', $approved);
        $this->assertStringContainsString("'quality_status' => \$umkm->getAttribute('quality_status')", $resolver);
        $this->assertStringNotContainsString("'quality_status' => 'Status", $resolver);
    }

    public function test_source_owned_official_update_has_explicit_guard(): void
    {
        $service = file_get_contents(app_path('Services/AdminDinas/UmkmOfficialService.php'));
        $this->assertIsString($service);
        $this->assertStringContainsString('assertNotSourceOwned', $service);
        $this->assertStringContainsString("getAttribute('source_system')", $service);
        $this->assertStringContainsString('Use the approved profile override workflow', $service);
    }

    public function test_periodic_reporting_is_not_part_of_profile_override_payload(): void
    {
        foreach ([
            app_path('Services/PelakuUmkm/EffectiveUmkmProfileService.php'),
            app_path('Services/PelakuUmkm/ApprovedProfileOverrideService.php'),
            app_path('Http/Requests/PelakuUmkm/SubmitProfileOverrideRequest.php'),
        ] as $path) {
            $source = file_get_contents($path);
            $this->assertIsString($source);
            $this->assertStringNotContainsString('monthly_revenue', $source);
            $this->assertStringNotContainsString('production_volume', $source);
            $this->assertStringNotContainsString('monitoring_period_id', $source);
        }
    }
}