<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('setting_group', 80)->nullable()->index();
            $table->string('label', 160)->nullable();
            $table->text('value')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->unsignedBigInteger('updated_by_user_id')->nullable()->index();
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            'key' => 'ui.active_theme',
            'setting_group' => 'appearance',
            'label' => 'Tema aktif sistem',
            'value' => 'green',
            'is_public' => true,
            'updated_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};