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
            // Invoicing Policy: 'recharge' (on topup) or 'usage' (on energy consumption)
            $table->string('invoicing_policy')->default('recharge');
            
            // NIT Requirement: 'optional' (allow use without nit) or 'required' (block use without nit)
            $table->string('nit_requirement_policy')->default('optional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['invoicing_policy', 'nit_requirement_policy']);
        });
    }
};
