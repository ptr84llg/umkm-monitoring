<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'users',
            'roles',
            'permissions',
            'role_permissions',
            'umkms',
            'umkm_update_submissions',
            'data_validation_reviews',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Checkpoint 10D requires table: {$table}");
            }
        }

        if (Schema::hasTable('umkm_profile_override_revisions')
            || Schema::hasTable('umkm_current_profile_overrides')) {
            throw new RuntimeException('Checkpoint 10D override tables already exist. Migration aborted.');
        }

        Schema::create('umkm_profile_override_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkms')->restrictOnDelete();
            $table->foreignId('source_submission_id')
                ->unique()
                ->constrained('umkm_update_submissions')
                ->restrictOnDelete();
            $table->foreignId('approved_review_id')
                ->unique()
                ->constrained('data_validation_reviews')
                ->restrictOnDelete();
            $table->foreignId('previous_override_revision_id')->nullable();
            $table->foreign('previous_override_revision_id', 'umkm_profile_override_prev_fk')
                ->references('id')
                ->on('umkm_profile_override_revisions')
                ->restrictOnDelete();
            $table->json('override_data');
            $table->foreignId('approved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->index();
            $table->timestamps();

            $table->index(['umkm_id', 'approved_at'], 'umkm_profile_override_revision_umkm_time_idx');
        });

        Schema::create('umkm_current_profile_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('umkm_id')->unique()->constrained('umkms')->restrictOnDelete();
            $table->foreignId('override_revision_id')
                ->unique()
                ->constrained('umkm_profile_override_revisions')
                ->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['code' => 'umkm.profile.propose'],
            [
                'name' => 'Propose Own UMKM Profile Change',
                'module' => 'umkm',
                'description' => 'Submit profile-change proposals for UMKM with an active verified ownership binding without mutating source data.',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $roleId = DB::table('roles')
            ->where('code', 'pelaku_umkm')
            ->where('is_active', true)
            ->value('id');

        $permissionId = DB::table('permissions')
            ->where('code', 'umkm.profile.propose')
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
            ? DB::table('permissions')->where('code', 'umkm.profile.propose')->value('id')
            : null;

        if ($permissionId && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        }

        if ($permissionId) {
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('umkm_current_profile_overrides');
        Schema::dropIfExists('umkm_profile_override_revisions');
    }
};