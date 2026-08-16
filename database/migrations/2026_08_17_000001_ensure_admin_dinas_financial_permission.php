<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['code' => 'umkm.sensitive.financial'],
            [
                'name' => 'View Sensitive Financial',
                'module' => 'umkm',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $roleId = DB::table('roles')->where('code', 'admin_dinas')->value('id');
        $permissionId = DB::table('permissions')->where('code', 'umkm.sensitive.financial')->value('id');

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
        $roleId = DB::table('roles')->where('code', 'admin_dinas')->value('id');
        $permissionId = DB::table('permissions')->where('code', 'umkm.sensitive.financial')->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }

        // Permission tidak dihapus karena merupakan permission keamanan reusable
        // dan telah didefinisikan juga pada RolePermissionSeeder.
    }
};