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
            // Rename columns to match Rafael's requested terminology exactly
            if (Schema::hasColumn('system_settings', 'libelula_canal_sucursal')) {
                $table->renameColumn('libelula_canal_sucursal', 'libelula_canal_caja_sucursal');
            }
            if (Schema::hasColumn('system_settings', 'libelula_canal_usuario')) {
                $table->renameColumn('libelula_canal_usuario', 'libelula_canal_caja_usuario');
            }
            
            // Add libelula_sector_code if missing
            if (!Schema::hasColumn('system_settings', 'libelula_sector_code')) {
                $table->string('libelula_sector_code')->default('1');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->renameColumn('libelula_canal_caja_sucursal', 'libelula_canal_sucursal');
            $table->renameColumn('libelula_canal_caja_usuario', 'libelula_canal_usuario');
            $table->dropColumn('libelula_sector_code');
        });
    }
};
