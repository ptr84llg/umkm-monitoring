<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncUmkmSourceLifecycle();
        $this->syncOwnerSourceFields();
        $this->syncBaselineSourceFields();
        $this->syncLegalitySourceFields();
        $this->createLssSyncAuditTables();
    }

    public function down(): void
    {
        // Intentionally non-destructive. These columns/tables contain imported
        // source provenance and audit history that must not be removed by rollback.
    }

    private function syncUmkmSourceLifecycle(): void
    {
        if (! Schema::hasTable('umkms')) {
            return;
        }

        Schema::table('umkms', function (Blueprint $table): void {
            if (! Schema::hasColumn('umkms', 'source_hash_version')) {
                $table->string('source_hash_version', 30)->nullable()->after('source_detail_hash');
            }

            if (! Schema::hasColumn('umkms', 'source_first_seen_at')) {
                $table->timestamp('source_first_seen_at')->nullable()->after('lss_detail_synced_at');
            }

            if (! Schema::hasColumn('umkms', 'source_last_seen_at')) {
                $table->timestamp('source_last_seen_at')->nullable()->after('source_first_seen_at');
            }

            if (! Schema::hasColumn('umkms', 'source_missing_since')) {
                $table->timestamp('source_missing_since')->nullable()->after('source_last_seen_at');
            }

            if (! Schema::hasColumn('umkms', 'source_active')) {
                $table->boolean('source_active')->default(true)->after('source_missing_since');
            }

            if (! Schema::hasColumn('umkms', 'source_sync_status')) {
                $table->string('source_sync_status', 30)->nullable()->after('source_active');
            }
        });

        if (
            Schema::hasColumn('umkms', 'source_system')
            && Schema::hasColumn('umkms', 'source_record_id')
            && ! Schema::hasIndex('umkms', 'uq_umkms_source_system_record')
        ) {
            $hasDuplicates = DB::table('umkms')
                ->select('source_system', 'source_record_id')
                ->whereNotNull('source_system')
                ->whereNotNull('source_record_id')
                ->groupBy('source_system', 'source_record_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if ($hasDuplicates) {
                throw new \RuntimeException('Tidak dapat menambah uq_umkms_source_system_record: terdapat duplicate source pair yang harus diaudit terlebih dahulu.');
            }

            Schema::table('umkms', function (Blueprint $table): void {
                $table->unique(['source_system', 'source_record_id'], 'uq_umkms_source_system_record');
            });
        }

        if (
            Schema::hasColumn('umkms', 'source_system')
            && Schema::hasColumn('umkms', 'source_active')
            && ! Schema::hasIndex('umkms', 'idx_umkms_source_active')
        ) {
            Schema::table('umkms', function (Blueprint $table): void {
                $table->index(['source_system', 'source_active'], 'idx_umkms_source_active');
            });
        }
    }

    private function syncOwnerSourceFields(): void
    {
        if (! Schema::hasTable('umkm_owners')) {
            return;
        }

        Schema::table('umkm_owners', function (Blueprint $table): void {
            if (! Schema::hasColumn('umkm_owners', 'owner_nik_masked')) {
                $table->string('owner_nik_masked', 32)->nullable()->after('owner_nik');
            }

            if (! Schema::hasColumn('umkm_owners', 'lss_detail_synced_at')) {
                $table->timestamp('lss_detail_synced_at')->nullable()->after('owner_nik_masked');
            }
        });

        if (Schema::hasColumn('umkm_owners', 'owner_nik_masked') && ! Schema::hasIndex('umkm_owners', 'idx_umkm_owners_nik_masked')) {
            Schema::table('umkm_owners', function (Blueprint $table): void {
                $table->index('owner_nik_masked', 'idx_umkm_owners_nik_masked');
            });
        }

        if (Schema::hasColumn('umkm_owners', 'lss_detail_synced_at') && ! Schema::hasIndex('umkm_owners', 'idx_umkm_owners_lss_detail_synced')) {
            Schema::table('umkm_owners', function (Blueprint $table): void {
                $table->index('lss_detail_synced_at', 'idx_umkm_owners_lss_detail_synced');
            });
        }
    }

    private function syncBaselineSourceFields(): void
    {
        if (! Schema::hasTable('umkm_baseline_profiles')) {
            return;
        }

        Schema::table('umkm_baseline_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('umkm_baseline_profiles', 'capital_amount')) {
                $table->decimal('capital_amount', 18, 2)->nullable()->after('marketing_method_id');
            }

            if (! Schema::hasColumn('umkm_baseline_profiles', 'annual_sales_amount')) {
                $table->decimal('annual_sales_amount', 18, 2)->nullable()->after('capital_amount');
            }

            if (! Schema::hasColumn('umkm_baseline_profiles', 'baseline_monthly_revenue')) {
                $table->decimal('baseline_monthly_revenue', 18, 2)->nullable()->after('annual_sales_amount');
            }

            if (! Schema::hasColumn('umkm_baseline_profiles', 'loan_amount')) {
                $table->decimal('loan_amount', 18, 2)->nullable()->after('baseline_monthly_revenue');
            }

            if (! Schema::hasColumn('umkm_baseline_profiles', 'loan_source')) {
                $table->string('loan_source', 150)->nullable()->after('loan_amount');
            }

            if (! Schema::hasColumn('umkm_baseline_profiles', 'lss_detail_synced_at')) {
                $table->timestamp('lss_detail_synced_at')->nullable()->after('loan_source');
            }
        });

        $indexes = [
            'idx_umkm_baseline_capital' => 'capital_amount',
            'idx_umkm_baseline_annual_sales' => 'annual_sales_amount',
            'idx_umkm_baseline_monthly_revenue' => 'baseline_monthly_revenue',
            'idx_umkm_baseline_lss_detail_synced' => 'lss_detail_synced_at',
        ];

        foreach ($indexes as $index => $column) {
            if (Schema::hasColumn('umkm_baseline_profiles', $column) && ! Schema::hasIndex('umkm_baseline_profiles', $index)) {
                Schema::table('umkm_baseline_profiles', function (Blueprint $table) use ($column, $index): void {
                    $table->index($column, $index);
                });
            }
        }
    }

    private function syncLegalitySourceFields(): void
    {
        if (! Schema::hasTable('umkm_legalities')) {
            return;
        }

        Schema::table('umkm_legalities', function (Blueprint $table): void {
            if (! Schema::hasColumn('umkm_legalities', 'nib_number_masked')) {
                $table->string('nib_number_masked')->nullable()->after('nib_number');
            }

            if (! Schema::hasColumn('umkm_legalities', 'lss_detail_synced_at')) {
                $table->timestamp('lss_detail_synced_at')->nullable()->after('status_data');
            }
        });

        if (Schema::hasColumn('umkm_legalities', 'nib_number_masked') && ! Schema::hasIndex('umkm_legalities', 'idx_umkm_legalities_nib_masked')) {
            Schema::table('umkm_legalities', function (Blueprint $table): void {
                $table->index('nib_number_masked', 'idx_umkm_legalities_nib_masked');
            });
        }

        if (Schema::hasColumn('umkm_legalities', 'lss_detail_synced_at') && ! Schema::hasIndex('umkm_legalities', 'idx_umkm_legalities_lss_detail_synced')) {
            Schema::table('umkm_legalities', function (Blueprint $table): void {
                $table->index('lss_detail_synced_at', 'idx_umkm_legalities_lss_detail_synced');
            });
        }
    }

    private function createLssSyncAuditTables(): void
    {
        if (! Schema::hasTable('lss_sync_runs')) {
            Schema::create('lss_sync_runs', function (Blueprint $table): void {
                $table->id();
                $table->string('snapshot_id', 100)->unique('uq_lss_sync_runs_snapshot');
                $table->string('source_system', 50)->default('LSS');
                $table->string('source_page', 500)->nullable();
                $table->string('json_path', 1000)->nullable();
                $table->char('json_sha256', 64)->nullable();
                $table->unsignedInteger('source_total_records')->nullable();
                $table->unsignedInteger('snapshot_record_count')->default(0);
                $table->unsignedInteger('detail_success_count')->default(0);
                $table->unsignedInteger('detail_failed_count')->default(0);
                $table->unsignedInteger('detail_pending_count')->default(0);
                $table->unsignedInteger('matched_count')->default(0);
                $table->unsignedInteger('new_count')->default(0);
                $table->unsignedInteger('rehash_required_count')->default(0);
                $table->unsignedInteger('changed_count')->default(0);
                $table->unsignedInteger('unchanged_count')->default(0);
                $table->unsignedInteger('missing_count')->default(0);
                $table->unsignedInteger('protected_count')->default(0);
                $table->unsignedInteger('suspicious_count')->default(0);
                $table->string('mode', 20)->nullable();
                $table->string('status', 30)->default('prepared')->index('idx_lss_sync_runs_status');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lss_sync_record_states')) {
            Schema::create('lss_sync_record_states', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('sync_run_id')->constrained('lss_sync_runs')->cascadeOnDelete();
                $table->foreignId('umkm_id')->nullable()->constrained('umkms')->nullOnDelete();
                $table->string('source_record_id', 50);
                $table->string('change_type', 30);
                $table->char('record_hash', 64)->nullable();
                $table->char('detail_hash', 64)->nullable();
                $table->string('detail_status', 30)->nullable();
                $table->boolean('is_present')->default(true);
                $table->string('notes', 500)->nullable();
                $table->timestamps();

                $table->unique(['sync_run_id', 'source_record_id'], 'uq_lss_sync_state_run_record');
                $table->index('source_record_id', 'idx_lss_sync_state_record');
                $table->index('umkm_id', 'idx_lss_sync_state_umkm');
            });
        }
    }
};
