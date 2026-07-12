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
            $table->integer('target_soc')->nullable()->default(80)->after('apply_discount_to_app');
            $table->text('soc_reached_message')->nullable()->after('target_soc');
        });
        
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->timestamp('soc_notification_sent_at')->nullable()->after('current_soc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn(['target_soc', 'soc_reached_message']);
        });
        
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn('soc_notification_sent_at');
        });
    }
};
