<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flattening the structure for stability and simplicity (4 fixed blocks)
        Schema::dropIfExists('tariff_periods');

        Schema::table('tariffs', function (Blueprint $table) {
            // Block 1 (Default)
            $table->time('b1_start')->default('00:00:00');
            $table->time('b1_end')->default('23:59:59');
            $table->decimal('b1_price_kwh', 10, 4)->default(0);
            $table->decimal('b1_price_min', 10, 4)->default(0);

            // Block 2
            $table->time('b2_start')->nullable();
            $table->time('b2_end')->nullable();
            $table->decimal('b2_price_kwh', 10, 4)->nullable();
            $table->decimal('b2_price_min', 10, 4)->nullable();

            // Block 3
            $table->time('b3_start')->nullable();
            $table->time('b3_end')->nullable();
            $table->decimal('b3_price_kwh', 10, 4)->nullable();
            $table->decimal('b3_price_min', 10, 4)->nullable();

            // Block 4
            $table->time('b4_start')->nullable();
            $table->time('b4_end')->nullable();
            $table->decimal('b4_price_kwh', 10, 4)->nullable();
            $table->decimal('b4_price_min', 10, 4)->nullable();
        });
    }

    public function down(): void
    {
        // Irreversible flattening for this session context
    }
};
