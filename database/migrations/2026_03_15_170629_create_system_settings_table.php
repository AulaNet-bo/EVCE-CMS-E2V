<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform_name')->default('E2V Charging Network');
            $table->text('disclaimer_text')->nullable();
            $table->boolean('is_disclaimer_visible')->default(true);

            // App Aesthetics
            $table->string('primary_color')->default('#4F46E5'); // Indigo
            $table->string('secondary_color')->default('#10B981'); // Emerald
            $table->string('button_color')->default('#4F46E5');
            $table->string('text_color')->default('#111827'); // Gray 900
            $table->string('font_family')->default('Inter');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
