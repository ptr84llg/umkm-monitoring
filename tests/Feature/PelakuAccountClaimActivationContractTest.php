<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PelakuAccountClaimActivationContractTest extends TestCase
{
    public function test_checkpoint_10a_source_does_not_create_ownership_binding(): void
    {
        $paths = [
            app_path('Services/PelakuUmkm/AccountClaimActivationService.php'),
            app_path('Http/Controllers/PelakuUmkm/AccountClaimController.php'),
            app_path('Http/Controllers/AdminDinas/UmkmAccountClaimReviewController.php'),
            database_path('migrations/2026_08_17_000003_create_umkm_account_claim_activation.php'),
        ];

        foreach ($paths as $path) {
            $source = file_get_contents($path);
            $this->assertIsString($source);
            $this->assertStringNotContainsString('umkm_user_links', $source);
        }
    }

    public function test_checkpoint_10a_exposes_claim_activation_but_not_pelaku_workspace(): void
    {
        $this->assertTrue(Route::has('pelaku-claim.create'));
        $this->assertTrue(Route::has('pelaku-activation.activate'));
        $this->assertTrue(Route::has('admin-dinas.account-claims.index'));
        $this->assertFalse(Route::has('pelaku-umkm.dashboard'));
    }

    public function test_pelaku_claim_request_has_no_quality_or_provenance_input(): void
    {
        $request = file_get_contents(
            app_path('Http/Requests/PelakuUmkm/SubmitUmkmAccountClaimRequest.php')
        );

        foreach ([
            'quality_status',
            'source_system',
            'source_record_id',
            'source_active',
            'provenance',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $request);
        }
    }

    public function test_activation_service_never_writes_umkm_profile_fields(): void
    {
        $service = file_get_contents(
            app_path('Services/PelakuUmkm/AccountClaimActivationService.php')
        );

        foreach ([
            "'quality_status' =>",
            "'business_name' =>",
            "'source_system' =>",
            "'source_record_id' =>",
            "'source_active' =>",
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $service);
        }
    }
}