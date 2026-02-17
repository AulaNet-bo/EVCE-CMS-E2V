<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the new periods table
        Schema::create('tariff_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_id')->constrained()->cascadeOnDelete();
            $table->time('start_time')->default('00:00:00');
            $table->time('end_time')->default('23:59:59');
            
            $table->decimal('price_kwh', 10, 4)->default(0);
            $table->decimal('price_min', 10, 4)->default(0); // Parking/Time fee
            
            $table->timestamps();
        });

        // 2. Remove old columns from tariffs (cleanup)
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn(['price_kwh', 'price_min']);
            // We keep 'price_session' and 'free_minutes' in the parent as global rules for now,
            // or the user might want them per block too? 
            // The user said "precios de parque y demas por bloques". 
            // Let's keep connection fee global for simplicity unless specified otherwise, 
            // but park/energy definitely go to periods.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_periods');
        Schema::table('tariffs', function (Blueprint $table) {
            $table->decimal('price_kwh', 10, 4)->default(0);
            $table->decimal('price_min', 10, 4)->default(0);
        });
    }
};
