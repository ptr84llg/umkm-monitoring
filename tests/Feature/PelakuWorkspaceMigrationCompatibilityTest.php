<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PelakuWorkspaceMigrationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_permission_migration_replays_on_fresh_test_database(): void
    {
        $this->assertDatabaseHas('permissions', [
            'code' => 'umkm.workspace.access',
            'module' => 'umkm',
        ]);
    }

    public function test_workspace_permission_attaches_when_active_pelaku_role_exists(): void
    {
        $migration = require database_path('migrations/2026_08_17_000005_ensure_pelaku_workspace_permission.php');
        $migration->down();

        DB::table('roles')->updateOrInsert(
            ['code' => 'pelaku_umkm'],
            [
                'name' => 'Pelaku UMKM',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $roleId = (int) DB::table('roles')
            ->where('code', 'pelaku_umkm')
            ->value('id');

        $migration->up();

        $permissionId = DB::table('permissions')
            ->where('code', 'umkm.workspace.access')
            ->value('id');

        $this->assertNotNull($permissionId);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);
    }
}