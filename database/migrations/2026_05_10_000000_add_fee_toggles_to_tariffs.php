<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->boolean('is_parking_fee_enabled')->default(true)->after('price_session');
            $table->boolean('is_time_fee_enabled')->default(true)->after('b1_price_min');
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn(['is_parking_fee_enabled', 'is_time_fee_enabled']);
        });
    }
};
