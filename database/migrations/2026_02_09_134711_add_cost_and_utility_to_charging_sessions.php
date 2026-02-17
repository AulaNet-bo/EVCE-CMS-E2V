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
        Schema::table('charging_sessions', function (Blueprint $table) {
            // Cost to the Operator (e.g. Utility Price)
            $table->decimal('utility_cost', 10, 4)->default(0)->after('total_cost');
            
            // Profit Margin (Calculated)
            $table->decimal('margin', 10, 4)->default(0)->after('utility_cost');
            
            // Link to Tariff used
            // Already has tariff_id, but let's store the specific rates used in case tariff changes later?
            // For now, tariff_id is sufficient, but let's add rate_kwh for audit.
            $table->decimal('rate_kwh', 10, 4)->nullable()->after('tariff_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn(['utility_cost', 'margin', 'rate_kwh']);
        });
    }
};
