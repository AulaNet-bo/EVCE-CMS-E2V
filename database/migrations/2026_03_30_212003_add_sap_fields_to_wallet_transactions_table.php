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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wallet_transactions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('wallet_transactions', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('status');
            }
            if (!Schema::hasColumn('wallet_transactions', 'bank_receipt_number')) {
                $table->string('bank_receipt_number')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('wallet_transactions', 'pos_correlative')) {
                $table->string('pos_correlative')->nullable()->after('bank_receipt_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'payment_method', 'bank_receipt_number', 'pos_correlative']);
        });
    }
};
