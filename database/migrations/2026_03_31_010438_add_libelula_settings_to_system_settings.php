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
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('libelula_app_key')->nullable()->after('font_family');
            $table->string('libelula_api_url')->nullable()->default('https://api.libelula.bo/rest')->after('libelula_app_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['libelula_app_key', 'libelula_api_url']);
        });
    }
};
