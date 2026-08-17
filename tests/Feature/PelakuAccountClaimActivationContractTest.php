<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PelakuAccountClaimActivationContractTest extends TestCase
{
    public function test_checkpoint_10b_binding_is_created_only_from_activation_path(): void
    {
        $activationService = file_get_contents(
            app_path('Services/PelakuUmkm/AccountClaimActivationService.php')
        );
        $bindingService = file_get_contents(
            app_path('Services/PelakuUmkm/OwnershipBindingService.php')
        );

        $this->assertIsString($activationService);
        $this->assertIsString($bindingService);

        $this->assertStringContainsString(
            'createFromActivatedClaim',
            $activationService
        );
        $this->assertStringContainsString(
            'UmkmAccountClaim::STATUS_ACTIVATED',
            $bindingService
        );
        $this->assertStringContainsString(
            "'source_claim_id' => \$claim->id",
            $bindingService
        );
        $this->assertStringContainsString(
            'BINDING_SOURCE_ACCOUNT_CLAIM_ACTIVATION',
            $bindingService
        );
        $this->assertStringContainsString(
            'VERIFICATION_VERIFIED',
            $bindingService
        );

        foreach ([
            app_path('Http/Controllers/PelakuUmkm/AccountClaimController.php'),
            app_path('Http/Controllers/AdminDinas/UmkmAccountClaimReviewController.php'),
        ] as $controllerPath) {
            $controller = file_get_contents($controllerPath);
            $this->assertIsString($controller);
            $this->assertStringNotContainsString(
                'UmkmUserLink::query()->create',
                $controller
            );
            $this->assertStringNotContainsString(
                "DB::table('umkm_user_links')->insert",
                $controller
            );
        }
    }

    public function test_checkpoint_10b_keeps_pelaku_workspace_disabled(): void
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

    public function test_activation_and_binding_services_never_write_umkm_profile_fields(): void
    {
        $sources = [
            file_get_contents(
                app_path('Services/PelakuUmkm/AccountClaimActivationService.php')
            ),
            file_get_contents(
                app_path('Services/PelakuUmkm/OwnershipBindingService.php')
            ),
        ];

        foreach ($sources as $source) {
            $this->assertIsString($source);

            foreach ([
                "'quality_status' =>",
                "'business_name' =>",
                "'source_system' =>",
                "'source_record_id' =>",
                "'source_active' =>",
            ] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $source);
            }
        }
    }
}