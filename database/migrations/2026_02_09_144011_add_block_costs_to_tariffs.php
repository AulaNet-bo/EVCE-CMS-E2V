<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            // Add COST per kWh for each block
            $table->decimal('b1_cost_kwh', 10, 4)->default(0)->after('b1_price_kwh');
            $table->decimal('b2_cost_kwh', 10, 4)->nullable()->after('b2_price_kwh');
            $table->decimal('b3_cost_kwh', 10, 4)->nullable()->after('b3_price_kwh');
            $table->decimal('b4_cost_kwh', 10, 4)->nullable()->after('b4_price_kwh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn(['b1_cost_kwh', 'b2_cost_kwh', 'b3_cost_kwh', 'b4_cost_kwh']);
        });
    }
};
