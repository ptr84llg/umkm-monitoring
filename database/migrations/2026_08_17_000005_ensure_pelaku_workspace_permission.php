<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['roles', 'permissions', 'role_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Checkpoint 10C requires table: {$table}");
            }
        }

        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['code' => 'umkm.workspace.access'],
            [
                'name' => 'Access Pelaku UMKM Workspace',
                'module' => 'umkm',
                'description' => 'Access the read-only Pelaku UMKM workspace when an active verified ownership binding exists.',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $roleId = DB::table('roles')
            ->where('code', 'pelaku_umkm')
            ->where('is_active', true)
            ->value('id');

        $permissionId = DB::table('permissions')
            ->where('code', 'umkm.workspace.access')
            ->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('code', 'umkm.workspace.access')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('permission_id', $permissionId)
                ->delete();
        }

        DB::table('permissions')
            ->where('id', $permissionId)
            ->delete();
    }
};