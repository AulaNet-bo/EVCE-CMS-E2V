<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sap_client_code')->nullable()->after('id');
            $table->timestamp('sap_synced_at')->nullable();
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('bank_receipt_number')->nullable()->after('invoice_url');
            $table->string('pos_correlative')->nullable()->after('bank_receipt_number');
            $table->string('payment_method')->nullable()->after('pos_correlative');
            $table->timestamp('sap_synced_at')->nullable();
        });

        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->string('item_code')->nullable()->after('currency');
            $table->string('item_description')->nullable()->after('item_code');
            $table->timestamp('sap_synced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sap_client_code', 'sap_synced_at']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn(['bank_receipt_number', 'pos_correlative', 'payment_method', 'sap_synced_at']);
        });

        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn(['item_code', 'item_description', 'sap_synced_at']);
        });
    }
};
