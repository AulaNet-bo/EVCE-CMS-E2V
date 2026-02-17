<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Re-creating connectors table properly
        if (Schema::hasTable('connectors')) {
            Schema::drop('connectors');
        }

        Schema::create('connectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->integer('connector_id'); // 1, 2, 3...
            $table->string('type')->default('Type 2'); // CCS, Type 2, CHAdeMO
            $table->decimal('max_power_kw', 8, 2)->default(22.00);
            $table->string('status')->default('Unavailable'); // Available, Charging, Faulted...
            $table->timestamps();
            
            $table->unique(['station_id', 'connector_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connectors');
    }
};
