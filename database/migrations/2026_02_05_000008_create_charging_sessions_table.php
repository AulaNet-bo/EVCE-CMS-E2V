<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charging_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique()->nullable(); // OCPP Transaction ID
            $table->foreignId('station_id')->constrained();
            $table->foreignId('connector_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('rfid_tag_id')->nullable()->constrained();
            $table->foreignId('tariff_id')->nullable()->constrained();
            
            $table->timestamp('start_time');
            $table->timestamp('stop_time')->nullable();
            
            $table->integer('meter_start')->default(0); // Wh
            $table->integer('meter_stop')->default(0);  // Wh
            $table->decimal('total_energy_kwh', 10, 4)->default(0);
            
            $table->decimal('total_cost', 10, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            
            $table->string('status')->default('Active'); // Active, Completed, Faulted
            $table->string('stop_reason')->nullable(); // Local, Remote, EVDisconnected
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charging_sessions');
    }
};
