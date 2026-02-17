<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('currency', 3)->default('USD'); // or BOB
            $table->decimal('price_kwh', 10, 4)->default(0);
            $table->decimal('price_min', 10, 4)->default(0); // Parking fee / active charging fee
            $table->decimal('price_session', 10, 4)->default(0); // Start fee
            $table->integer('free_minutes')->default(0); // Grace period
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
