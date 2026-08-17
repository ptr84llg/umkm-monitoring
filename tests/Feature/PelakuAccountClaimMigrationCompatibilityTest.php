<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PelakuAccountClaimMigrationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkpoint_10a_migration_replays_on_fresh_test_database(): void
    {
        $this->assertTrue(Schema::hasTable('umkm_account_claims'));
        $this->assertTrue(Schema::hasTable('umkm_claim_activation_challenges'));
        $this->assertTrue(Schema::hasTable('umkm_account_claim_events'));

        $this->assertDatabaseHas('permissions', [
            'code' => 'umkm.claim.review',
            'module' => 'umkm',
        ]);
    }
}