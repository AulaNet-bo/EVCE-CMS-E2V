<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('vehicle_brand')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_plate')->nullable();
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('restrict_charging_without_vehicle')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn(['vehicle_id', 'vehicle_brand', 'vehicle_model', 'vehicle_plate']);
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('restrict_charging_without_vehicle');
        });
    }
};
