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
            if (!Schema::hasColumn('charging_sessions', 'session_fee')) {
                $table->decimal('session_fee', 10, 2)->default(0)->after('total_energy_kwh');
            }
            if (!Schema::hasColumn('charging_sessions', 'energy_cost')) {
                $table->decimal('energy_cost', 10, 4)->default(0)->after('session_fee');
            }
            if (!Schema::hasColumn('charging_sessions', 'time_fee')) {
                $table->decimal('time_fee', 10, 2)->default(0)->after('energy_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn(['session_fee', 'energy_cost', 'time_fee']);
        });
    }
};
