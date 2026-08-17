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
                throw new RuntimeException("Checkpoint 10E requires table: {$table}");
            }
        }

        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['code' => 'umkm.profile.review'],
            [
                'name' => 'Review Pelaku UMKM Profile Changes',
                'module' => 'umkm',
                'description' => 'Review immutable Pelaku UMKM profile-change submissions and activate approved effective-profile overrides without mutating source data.',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $roleId = DB::table('roles')
            ->where('code', 'admin_dinas')
            ->where('is_active', true)
            ->value('id');

        $permissionId = DB::table('permissions')
            ->where('code', 'umkm.profile.review')
            ->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $permissionId = Schema::hasTable('permissions')
            ? DB::table('permissions')->where('code', 'umkm.profile.review')->value('id')
            : null;

        if ($permissionId && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        }

        if ($permissionId) {
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};