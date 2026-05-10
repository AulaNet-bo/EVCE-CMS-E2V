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
        Schema::create('libelula_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('method', 20)->default('POST');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->integer('http_status')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->timestamps();

            // Opcional, relacionar con transacciones para búsquedas rápidas
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('libelula_api_logs');
    }
};
