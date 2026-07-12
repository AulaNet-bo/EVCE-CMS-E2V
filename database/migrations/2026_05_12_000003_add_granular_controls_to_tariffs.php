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
            if (!Schema::hasColumn('tariffs', 'apply_fee_to_cards')) {
                $table->boolean('apply_fee_to_cards')->default(false)->after('is_parking_fee_enabled');
            }
            if (!Schema::hasColumn('tariffs', 'apply_fee_to_app')) {
                $table->boolean('apply_fee_to_app')->default(true)->after('is_parking_fee_enabled');
            }
            if (!Schema::hasColumn('tariffs', 'apply_discount_to_cards')) {
                $table->boolean('apply_discount_to_cards')->default(false)->after('discount_fixed_amount');
            }
            if (!Schema::hasColumn('tariffs', 'apply_discount_to_app')) {
                $table->boolean('apply_discount_to_app')->default(true)->after('apply_discount_to_cards');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn([
                'apply_fee_to_cards',
                'apply_fee_to_app',
                'apply_discount_to_cards',
                'apply_discount_to_app'
            ]);
        });
    }
};
