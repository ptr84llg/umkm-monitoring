<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('code', 'system.manage')->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'code' => 'system.manage',
                'name' => 'Mengelola Pengaturan Sistem',
                'module' => 'governance',
                'description' => 'Mengelola pengaturan sistem internal, termasuk theme, governance, dan konfigurasi yang dijaga backend guard.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminUtamaRoleId = DB::table('roles')->where('code', 'admin_utama')->value('id');

        if (! $adminUtamaRoleId) {
            return;
        }

        $exists = DB::table('role_permissions')
            ->where('role_id', $adminUtamaRoleId)
            ->where('permission_id', $permissionId)
            ->exists();

        if (! $exists) {
            DB::table('role_permissions')->insert([
                'role_id' => $adminUtamaRoleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('code', 'system.manage')->value('id');
        $adminUtamaRoleId = DB::table('roles')->where('code', 'admin_utama')->value('id');

        if ($permissionId && $adminUtamaRoleId) {
            DB::table('role_permissions')
                ->where('role_id', $adminUtamaRoleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }
    }
};