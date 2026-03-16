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
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->decimal('session_fee', 10, 2)->default(0)->after('tariff_id');
            $table->decimal('time_fee', 10, 2)->default(0)->after('session_fee');
            $table->decimal('energy_cost', 10, 4)->default(0)->after('time_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn(['session_fee', 'time_fee', 'energy_cost']);
        });
    }
};
