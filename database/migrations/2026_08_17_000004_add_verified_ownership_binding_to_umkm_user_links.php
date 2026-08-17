<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('umkm_user_links')) {
            throw new \RuntimeException('Checkpoint 10B requires umkm_user_links.');
        }

        if (! Schema::hasTable('umkm_account_claims')) {
            throw new \RuntimeException('Checkpoint 10B requires Checkpoint 10A account claims.');
        }

        foreach ([
            'source_claim_id',
            'binding_source',
            'verification_status',
            'is_active',
            'verified_at',
            'verified_by_user_id',
            'revoked_at',
            'revoked_by_user_id',
            'revocation_reason',
        ] as $column) {
            if (Schema::hasColumn('umkm_user_links', $column)) {
                throw new \RuntimeException("Checkpoint 10B column already exists: umkm_user_links.{$column}");
            }
        }

        Schema::table('umkm_user_links', function (Blueprint $table): void {
            $table->foreignId('source_claim_id')
                ->nullable()
                ->constrained('umkm_account_claims')
                ->restrictOnDelete();
            $table->string('binding_source', 48)->nullable()->index();
            $table->string('verification_status', 32)->default('unverified')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('verified_at')->nullable()->index();
            $table->foreignId('verified_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->foreignId('revoked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('revocation_reason', 190)->nullable();

            $table->unique('source_claim_id', 'umkm_user_links_source_claim_unique');
            $table->index(
                ['user_id', 'is_active', 'verification_status'],
                'umkm_user_links_user_active_verified_idx'
            );
            $table->index(
                ['umkm_id', 'is_active', 'verification_status'],
                'umkm_user_links_umkm_active_verified_idx'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('umkm_user_links')) {
            return;
        }

        Schema::table('umkm_user_links', function (Blueprint $table): void {
            $table->dropIndex('umkm_user_links_user_active_verified_idx');
            $table->dropIndex('umkm_user_links_umkm_active_verified_idx');
        });

        Schema::table('umkm_user_links', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('source_claim_id');
            $table->dropConstrainedForeignId('verified_by_user_id');
            $table->dropConstrainedForeignId('revoked_by_user_id');
        });

        Schema::table('umkm_user_links', function (Blueprint $table): void {
            $table->dropColumn([
                'binding_source',
                'verification_status',
                'is_active',
                'verified_at',
                'revoked_at',
                'revocation_reason',
            ]);
        });
    }
};