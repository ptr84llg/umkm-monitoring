<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncUserColumns();
        $this->createBusinessReferenceTables();
        $this->createUserSecurityTables();
        $this->createUmkmProfileTables();
        $this->createLaravelRuntimeTables();
        $this->syncUmkmLocations();
        $this->addUserCurrentDeviceForeignKey();
    }

    public function down(): void
    {
        // This migration synchronizes the application with the provided final SQL schema.
        // Rollback is intentionally non-destructive because these tables may contain imported Dinas data.
    }

    private function syncUserColumns(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 80)->nullable()->unique();
            }

            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'auth_provider_required')) {
                $table->string('auth_provider_required', 32)->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'manual_login_disabled_at')) {
                $table->timestamp('manual_login_disabled_at')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'google_linked_at')) {
                $table->timestamp('google_linked_at')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable();
            }

            if (! Schema::hasColumn('users', 'current_device_id')) {
                $table->unsignedBigInteger('current_device_id')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'current_device_fingerprint_hash')) {
                $table->char('current_device_fingerprint_hash', 64)->nullable()->index('users_current_device_fingerprint_index');
            }

            if (! Schema::hasColumn('users', 'last_login_user_agent_hash')) {
                $table->char('last_login_user_agent_hash', 64)->nullable();
            }

            if (! Schema::hasColumn('users', 'last_login_device_label')) {
                $table->string('last_login_device_label', 120)->nullable();
            }

            if (! Schema::hasColumn('users', 'last_login_browser_label')) {
                $table->string('last_login_browser_label', 120)->nullable();
            }
        });
    }

    private function createBusinessReferenceTables(): void
    {
        if (! Schema::hasTable('business_category_references')) {
            Schema::create('business_category_references', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 150)->unique();
                $table->string('slug', 180)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('business_type_references')) {
            Schema::create('business_type_references', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 180)->unique();
                $table->string('slug', 220)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('marketing_method_references')) {
            Schema::create('marketing_method_references', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 80)->unique();
                $table->string('name', 120)->unique();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    private function createUserSecurityTables(): void
    {
        if (! Schema::hasTable('user_devices')) {
            Schema::create('user_devices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->char('device_fingerprint_hash', 64);
                $table->char('user_agent_hash', 64)->nullable();
                $table->string('device_label', 120)->nullable();
                $table->string('browser_label', 120)->nullable();
                $table->string('platform_label', 120)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->boolean('is_trusted')->default(false);
                $table->boolean('is_active')->default(false);
                $table->string('trust_reason', 120)->nullable();
                $table->timestamp('trusted_at')->nullable();
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_at')->nullable()->index('user_devices_last_seen_idx');
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'device_fingerprint_hash'], 'user_devices_user_fingerprint_unique');
                $table->index(['user_id', 'is_active', 'is_trusted'], 'user_devices_user_status_idx');
                $table->index('device_fingerprint_hash', 'user_devices_fingerprint_idx');
            });
        }

        if (! Schema::hasTable('auth_device_sessions')) {
            Schema::create('auth_device_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('user_device_id')->nullable()->constrained('user_devices')->nullOnDelete();
                $table->char('session_hash', 64)->nullable();
                $table->string('status', 32)->default('active');
                $table->string('login_method', 32)->default('manual');
                $table->string('ip_address', 45)->nullable();
                $table->char('user_agent_hash', 64)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('browser_label', 120)->nullable();
                $table->char('device_fingerprint_hash', 64)->nullable();
                $table->boolean('requires_otp')->default(false);
                $table->timestamp('otp_verified_at')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->string('revoke_reason', 120)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status'], 'auth_device_sessions_user_status_idx');
                $table->index(['user_device_id', 'status'], 'auth_device_sessions_device_status_idx');
                $table->index('session_hash', 'auth_device_sessions_session_hash_idx');
                $table->index('device_fingerprint_hash', 'auth_device_sessions_fingerprint_idx');
                $table->index('last_seen_at', 'auth_device_sessions_last_seen_idx');
            });
        }

        if (! Schema::hasTable('auth_otp_challenges')) {
            Schema::create('auth_otp_challenges', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('user_device_id')->nullable()->constrained('user_devices')->nullOnDelete();
                $table->char('challenge_token_hash', 64)->nullable();
                $table->string('purpose', 60);
                $table->string('delivery_channel', 32)->default('email');
                $table->string('sent_to_masked', 120)->nullable();
                $table->char('otp_hash', 64);
                $table->unsignedTinyInteger('attempt_count')->default(0);
                $table->unsignedTinyInteger('max_attempts')->default(5);
                $table->string('ip_address', 45)->nullable();
                $table->char('user_agent_hash', 64)->nullable();
                $table->char('device_fingerprint_hash', 64)->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('consumed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('status', 32)->default('pending');
                $table->timestamps();

                $table->index(['user_id', 'purpose', 'status'], 'auth_otp_user_purpose_status_idx');
                $table->index('challenge_token_hash', 'auth_otp_challenge_token_idx');
                $table->index('expires_at', 'auth_otp_expires_idx');
                $table->index('device_fingerprint_hash', 'auth_otp_fingerprint_idx');
            });
        }

        if (! Schema::hasTable('user_identity_credentials')) {
            Schema::create('user_identity_credentials', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('identifier_type', 32);
                $table->char('identifier_hash', 64);
                $table->string('identifier_masked', 64)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('login_enabled')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('login_enabled_at')->nullable();
                $table->foreignId('login_enabled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->unique(['identifier_type', 'identifier_hash'], 'uic_type_hash_unique');
                $table->index(['user_id', 'identifier_type', 'is_active', 'login_enabled'], 'uic_user_type_status_idx');
            });
        }
    }

    private function createUmkmProfileTables(): void
    {
        if (! Schema::hasTable('umkm_baseline_profiles')) {
            Schema::create('umkm_baseline_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('umkm_id')->unique()->constrained('umkms')->cascadeOnDelete();
                $table->unsignedInteger('employee_count')->nullable()->index();
                $table->foreignId('marketing_method_id')->nullable()->constrained('marketing_method_references')->nullOnDelete();
                $table->enum('status_data', ['diajukan', 'disetujui', 'resmi', 'terbatas'])->default('terbatas')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('umkm_business_classifications')) {
            Schema::create('umkm_business_classifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('umkm_id')->constrained('umkms')->cascadeOnDelete();
                $table->foreignId('business_category_id')->constrained('business_category_references')->restrictOnDelete();
                $table->foreignId('business_type_id')->constrained('business_type_references')->restrictOnDelete();
                $table->boolean('is_primary')->default(true)->index();
                $table->enum('status_data', ['diajukan', 'disetujui', 'resmi', 'terbatas'])->default('terbatas')->index();
                $table->timestamps();

                $table->unique(['umkm_id', 'business_category_id', 'business_type_id'], 'umkm_business_classifications_unique');
                $table->index('business_category_id', 'umkm_business_classifications_category_index');
                $table->index('business_type_id', 'umkm_business_classifications_type_index');
            });
        }

        if (! Schema::hasTable('umkm_data_quality_flags')) {
            Schema::create('umkm_data_quality_flags', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('umkm_id')->constrained('umkms')->cascadeOnDelete();
                $table->string('flag_code', 100);
                $table->string('flag_group', 80);
                $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
                $table->string('description')->nullable();
                $table->string('detected_value')->nullable();
                $table->enum('status', ['open', 'resolved', 'stale'])->default('open');
                $table->enum('source_type', ['auto', 'manual'])->default('auto');
                $table->timestamp('detected_at')->useCurrent();
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->unique(['umkm_id', 'flag_code'], 'uq_umkm_quality_flag');
                $table->index('umkm_id', 'idx_umkm_quality_umkm');
                $table->index('flag_code', 'idx_umkm_quality_code');
                $table->index('flag_group', 'idx_umkm_quality_group');
                $table->index('severity', 'idx_umkm_quality_severity');
                $table->index('status', 'idx_umkm_quality_status');
                $table->index('source_type', 'idx_umkm_quality_source_type');
            });
        }

        if (! Schema::hasTable('umkm_media')) {
            Schema::create('umkm_media', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('umkm_id')->constrained('umkms')->cascadeOnDelete();
                $table->string('media_type', 40)->default('image')->index();
                $table->string('media_role', 60)->default('profile')->index();
                $table->string('source_path', 500)->nullable();
                $table->string('source_url', 1000)->nullable();
                $table->string('local_path', 500)->nullable();
                $table->char('source_hash', 64)->nullable();
                $table->string('caption')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_primary')->default(false)->index();
                $table->enum('visibility', ['private', 'internal', 'public_safe'])->default('internal')->index();
                $table->enum('status_data', ['draft', 'diajukan', 'perlu_validasi', 'aktif', 'arsip'])->default('diajukan')->index();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['umkm_id', 'source_hash'], 'umkm_media_umkm_id_source_hash_unique');
            });
        }
    }

    private function createLaravelRuntimeTables(): void
    {
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    private function syncUmkmLocations(): void
    {
        if (! Schema::hasTable('umkm_locations')) {
            return;
        }

        if (! Schema::hasColumn('umkm_locations', 'coordinate_status')) {
            Schema::table('umkm_locations', function (Blueprint $table): void {
                $table->enum('coordinate_status', ['terpetakan', 'belum_terpetakan', 'perlu_validasi'])
                    ->default('belum_terpetakan')
                    ->after('longitude');
            });
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->tryStatement('ALTER TABLE `umkm_locations` MODIFY `district_region_id` BIGINT UNSIGNED NULL');
        $this->tryStatement('ALTER TABLE `umkm_locations` MODIFY `village_region_id` BIGINT UNSIGNED NULL');
        $this->tryStatement('ALTER TABLE `umkm_locations` MODIFY `address_detail` TEXT NULL');
        $this->tryStatement('ALTER TABLE `umkm_locations` MODIFY `latitude` DECIMAL(10,7) NULL');
        $this->tryStatement('ALTER TABLE `umkm_locations` MODIFY `longitude` DECIMAL(10,7) NULL');

        foreach (['province_region_id', 'city_region_id', 'district_region_id', 'village_region_id'] as $column) {
            $this->ensureForeignReferences('umkm_locations', $column, 'regions');
        }
    }

    private function addUserCurrentDeviceForeignKey(): void
    {
        if (
            DB::getDriverName() !== 'mysql'
            || ! Schema::hasTable('users')
            || ! Schema::hasTable('user_devices')
            || ! Schema::hasColumn('users', 'current_device_id')
        ) {
            return;
        }

        $this->ensureForeignReferences('users', 'current_device_id', 'user_devices', 'SET NULL');
    }

    private function ensureForeignReferences(string $table, string $column, string $targetTable, string $onDelete = 'RESTRICT'): void
    {
        $constraint = $table . '_' . $column . '_foreign';
        $referencedTable = $this->referencedTable($table, $constraint);

        if ($referencedTable !== null && $referencedTable !== $targetTable) {
            $this->tryStatement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
            $referencedTable = null;
        }

        if ($referencedTable !== null) {
            return;
        }

        $deleteClause = strtoupper($onDelete) === 'SET NULL' ? 'SET NULL' : 'RESTRICT';
        $this->tryStatement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`{$column}`) REFERENCES `{$targetTable}` (`id`) ON DELETE {$deleteClause}");
    }

    private function referencedTable(string $table, string $constraint): ?string
    {
        try {
            $row = DB::selectOne(
                'select referenced_table_name from information_schema.KEY_COLUMN_USAGE where table_schema = DATABASE() and table_name = ? and constraint_name = ? and referenced_table_name is not null',
                [$table, $constraint]
            );

            return $row?->referenced_table_name;
        } catch (Throwable) {
            return null;
        }
    }

    private function tryStatement(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (Throwable) {
            // Existing compatible schemas may already have this alteration or constraint.
        }
    }
};
