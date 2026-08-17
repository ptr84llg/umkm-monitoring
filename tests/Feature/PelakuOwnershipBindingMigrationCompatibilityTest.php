<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PelakuOwnershipBindingMigrationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkpoint_10b_binding_schema_replays_on_fresh_test_database(): void
    {
        $this->assertTrue(Schema::hasTable('umkm_user_links'));

        foreach ([
            'source_claim_id',
            'binding_source',
            'verification_status',
            'is_active',
            'verified_at',
            'verified_by_user_id',
            'revoked_at',
            'revoked_by_user_id',
            'revocation_reason',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('umkm_user_links', $column),
                "Missing Checkpoint 10B column: {$column}"
            );
        }

        $this->assertSame(0, DB::table('umkm_user_links')->count());
    }
}