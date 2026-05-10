<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->foreignId('product_recharge_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_energy_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_connection_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_penalty_id')->nullable()->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropForeign(['product_recharge_id']);
            $table->dropForeign(['product_energy_id']);
            $table->dropForeign(['product_connection_id']);
            $table->dropForeign(['product_penalty_id']);
            $table->dropColumn(['product_recharge_id', 'product_energy_id', 'product_connection_id', 'product_penalty_id']);
        });
    }
};
