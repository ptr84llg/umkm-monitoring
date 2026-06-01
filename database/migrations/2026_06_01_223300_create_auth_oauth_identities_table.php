<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'auth_provider_required')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('auth_provider_required', 32)
                    ->nullable()
                    ->after('password')
                    ->index();
            });
        }

        if (! Schema::hasColumn('users', 'manual_login_disabled_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('manual_login_disabled_at')
                    ->nullable()
                    ->after('auth_provider_required')
                    ->index();
            });
        }

        if (! Schema::hasColumn('users', 'google_linked_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('google_linked_at')
                    ->nullable()
                    ->after('manual_login_disabled_at')
                    ->index();
            });
        }

        if (! Schema::hasTable('auth_oauth_identities')) {
            Schema::create('auth_oauth_identities', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('provider', 32)->index();
                $table->string('provider_id', 191);
                $table->string('provider_email')->nullable()->index();
                $table->char('provider_email_hash', 64)->nullable()->index();
                $table->boolean('provider_email_verified')->default(false)->index();
                $table->string('provider_name')->nullable();
                $table->string('provider_avatar', 2048)->nullable();

                $table->string('identity_type', 32)->default('public_limited')->index();
                $table->string('status', 32)->default('pending')->index();

                $table->timestamp('linked_at')->nullable()->index();
                $table->timestamp('cancelled_at')->nullable()->index();
                $table->timestamp('revoked_at')->nullable()->index();
                $table->timestamp('last_login_at')->nullable()->index();

                $table->string('last_login_ip', 45)->nullable();
                $table->char('last_user_agent_hash', 64)->nullable();
                $table->char('last_device_fingerprint_hash', 64)->nullable();

                $table->json('provider_payload_min')->nullable();
                $table->timestamps();

                $table->unique(['provider', 'provider_id'], 'auth_oauth_provider_id_unique');
                $table->index(['user_id', 'provider', 'status'], 'auth_oauth_user_provider_status_idx');
                $table->index(['identity_type', 'status'], 'auth_oauth_type_status_idx');
                $table->index(['provider', 'provider_email_hash'], 'auth_oauth_provider_email_hash_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_oauth_identities');

        if (Schema::hasColumn('users', 'google_linked_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('google_linked_at');
            });
        }

        if (Schema::hasColumn('users', 'manual_login_disabled_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('manual_login_disabled_at');
            });
        }

        if (Schema::hasColumn('users', 'auth_provider_required')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('auth_provider_required');
            });
        }
    }
};