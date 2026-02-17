<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dateTime('valid_from')->nullable()->after('free_minutes');
            $table->dateTime('valid_until')->nullable()->after('valid_from');

            $table->index(['valid_from', 'valid_until'], 'tariffs_valid_window_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropIndex('tariffs_valid_window_idx');
            $table->dropColumn(['valid_from', 'valid_until']);
        });
    }
};
