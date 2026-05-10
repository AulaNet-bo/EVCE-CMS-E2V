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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('siat_product_code')->nullable(); // Code from SIN
            $table->string('internal_code')->unique();
            $table->decimal('price', 10, 2)->nullable(); // Fixed price (if applicable)
            $table->string('unit_of_measure')->default('UNIDAD');
            $table->enum('type', ['fixed', 'service'])->default('fixed');
            $table->boolean('is_active')->default(true);
            $table->string('category')->nullable(); // e.g. Hardware, Energy
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
