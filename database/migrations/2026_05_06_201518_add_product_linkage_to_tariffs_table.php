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
            $table->foreignId('energy_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('connection_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('time_product_id')->nullable()->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropForeign(['energy_product_id']);
            $table->dropColumn('energy_product_id');
            $table->dropForeign(['connection_product_id']);
            $table->dropColumn('connection_product_id');
            $table->dropForeign(['time_product_id']);
            $table->dropColumn('time_product_id');
        });
    }
};
