<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('umkm_account_claims')
            || Schema::hasTable('umkm_claim_activation_challenges')
            || Schema::hasTable('umkm_account_claim_events')) {
            throw new RuntimeException('Checkpoint 10A claim tables already exist. Migration aborted.');
        }

        Schema::create('umkm_account_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkms')->restrictOnDelete();
            $table->string('claim_reference', 32)->unique();
            $table->string('claim_type', 32);
            $table->string('applicant_name', 190);
            $table->string('applicant_email', 190);
            $table->string('relationship_type', 32)->default('owner');
            $table->string('status', 48)->default('pending_review');
            $table->foreignId('activated_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resubmission_of_id')->nullable()->constrained('umkm_account_claims')->nullOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('activation_completed_at')->nullable();
            $table->timestamps();

            $table->index(['umkm_id', 'status'], 'umkm_account_claims_umkm_status_idx');
            $table->index(['applicant_email', 'status'], 'umkm_account_claims_email_status_idx');
            $table->index(['claim_type', 'status'], 'umkm_account_claims_type_status_idx');
        });

        Schema::create('umkm_claim_activation_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('claim_id')->constrained('umkm_account_claims')->restrictOnDelete();
            $table->char('challenge_token_hash', 64)->unique();
            $table->char('otp_hash', 64);
            $table->string('delivery_channel', 32)->default('email');
            $table->string('sent_to_masked', 120)->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->string('ip_address', 45)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamps();

            $table->index(['claim_id', 'status'], 'umkm_claim_activation_claim_status_idx');
            $table->index('expires_at', 'umkm_claim_activation_expires_idx');
        });

        Schema::create('umkm_account_claim_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('claim_id')->constrained('umkm_account_claims')->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 64);
            $table->string('from_status', 48)->nullable();
            $table->string('to_status', 48)->nullable();
            $table->json('event_detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamp('event_time');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['claim_id', 'event_time'], 'umkm_claim_events_claim_time_idx');
            $table->index(['event_type', 'event_time'], 'umkm_claim_events_type_time_idx');
        });

        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['code' => 'umkm.claim.review'],
            [
                'name' => 'Review Pelaku UMKM Account Claim',
                'module' => 'umkm',
                'description' => 'Review, approve, reject, invite, and resend Pelaku UMKM account activation claims.',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $roleId = DB::table('roles')
            ->where('code', 'admin_dinas')
            ->where('is_active', true)
            ->value('id');

        $permissionId = DB::table('permissions')
            ->where('code', 'umkm.claim.review')
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
        $roleId = DB::table('roles')->where('code', 'admin_dinas')->value('id');
        $permissionId = DB::table('permissions')->where('code', 'umkm.claim.review')->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }

        if ($permissionId) {
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('umkm_account_claim_events');
        Schema::dropIfExists('umkm_claim_activation_challenges');
        Schema::dropIfExists('umkm_account_claims');
    }
};