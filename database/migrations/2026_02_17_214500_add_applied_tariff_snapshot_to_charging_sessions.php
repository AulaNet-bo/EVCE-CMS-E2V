<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->foreignId('applied_tariff_id')->nullable()->after('tariff_id')->constrained('tariffs')->nullOnDelete();
            $table->json('applied_tariff_snapshot')->nullable()->after('applied_tariff_id');
            $table->dateTime('financial_locked_at')->nullable()->after('applied_tariff_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('applied_tariff_id');
            $table->dropColumn(['applied_tariff_snapshot', 'financial_locked_at']);
        });
    }
};
