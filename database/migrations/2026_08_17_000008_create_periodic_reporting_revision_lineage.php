<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['umkms', 'monitoring_periods', 'users', 'umkm_performance_records'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Checkpoint 10F requires table: {$table}");
            }
        }

        if (Schema::hasTable('umkm_performance_record_revisions')
            || Schema::hasTable('umkm_current_performance_revisions')) {
            throw new RuntimeException('Checkpoint 10F reporting lineage tables already exist. Migration aborted.');
        }

        if (DB::table('umkm_performance_records')->exists()) {
            throw new RuntimeException(
                'Legacy periodic performance rows exist without revision provenance. Explicit reconciliation is required; Checkpoint 10F will not infer history.'
            );
        }

        Schema::create('umkm_performance_record_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('umkm_id')->constrained('umkms')->restrictOnDelete();
            $table->foreignId('monitoring_period_id')->constrained('monitoring_periods')->restrictOnDelete();
            $table->foreignId('previous_revision_id')->nullable();
            $table->foreign('previous_revision_id', 'umkm_perf_rev_prev_fk')
                ->references('id')
                ->on('umkm_performance_record_revisions')
                ->restrictOnDelete();
            $table->unsignedInteger('revision_no');
            $table->decimal('monthly_revenue', 18, 2)->nullable();
            $table->integer('worker_count')->nullable();
            $table->integer('production_volume')->nullable();
            $table->string('status_data', 32)->default('draft');
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->string('revision_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(
                ['umkm_id', 'monitoring_period_id', 'revision_no'],
                'umkm_perf_rev_umkm_period_no_uq'
            );
            $table->index(
                ['monitoring_period_id', 'status_data'],
                'umkm_perf_rev_period_status_idx'
            );
        });

        Schema::create('umkm_current_performance_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('umkm_id');
            $table->foreign('umkm_id', 'umkm_curr_perf_umkm_fk')
                ->references('id')->on('umkms')->restrictOnDelete();
            $table->foreignId('monitoring_period_id');
            $table->foreign('monitoring_period_id', 'umkm_curr_perf_period_fk')
                ->references('id')->on('monitoring_periods')->restrictOnDelete();
            $table->foreignId('performance_revision_id');
            $table->unique('performance_revision_id', 'umkm_curr_perf_revision_uq');
            $table->foreign('performance_revision_id', 'umkm_curr_perf_revision_fk')
                ->references('id')->on('umkm_performance_record_revisions')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->nullable();
            $table->foreign('updated_by_user_id', 'umkm_curr_perf_user_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['umkm_id', 'monitoring_period_id'],
                'umkm_curr_perf_umkm_period_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_current_performance_revisions');
        Schema::dropIfExists('umkm_performance_record_revisions');
    }
};