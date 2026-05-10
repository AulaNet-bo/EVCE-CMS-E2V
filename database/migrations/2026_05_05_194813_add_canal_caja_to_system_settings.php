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
            $table->string('libelula_canal_caja')->nullable();
            $table->string('libelula_canal_caja_sucursal')->nullable();
            $table->string('libelula_canal_caja_usuario')->nullable();
            $table->string('libelula_sector_code')->default('1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'libelula_canal_caja', 
                'libelula_canal_caja_sucursal', 
                'libelula_canal_caja_usuario',
                'libelula_sector_code'
            ]);
        });
    }
};
