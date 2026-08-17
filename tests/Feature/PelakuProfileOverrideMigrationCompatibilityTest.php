<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PelakuProfileOverrideMigrationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkpoint_10d_override_schema_replays_on_fresh_database(): void
    {
        $this->assertTrue(Schema::hasTable('umkm_profile_override_revisions'));
        $this->assertTrue(Schema::hasTable('umkm_current_profile_overrides'));

        foreach ([
            'umkm_id',
            'source_submission_id',
            'approved_review_id',
            'previous_override_revision_id',
            'override_data',
            'approved_by_user_id',
            'approved_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('umkm_profile_override_revisions', $column));
        }

        $this->assertDatabaseHas('permissions', [
            'code' => 'umkm.profile.propose',
            'module' => 'umkm',
        ]);
        $this->assertSame(0, DB::table('umkm_profile_override_revisions')->count());
        $this->assertSame(0, DB::table('umkm_current_profile_overrides')->count());
    }
}