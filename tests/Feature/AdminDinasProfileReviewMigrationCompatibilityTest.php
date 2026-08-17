<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDinasProfileReviewMigrationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkpoint_10e_permission_replays_on_fresh_database(): void
    {
        $this->assertDatabaseHas('permissions', [
            'code' => 'umkm.profile.review',
            'module' => 'umkm',
        ]);

        $this->assertSame(
            1,
            DB::table('permissions')->where('code', 'umkm.profile.review')->count()
        );
    }
}