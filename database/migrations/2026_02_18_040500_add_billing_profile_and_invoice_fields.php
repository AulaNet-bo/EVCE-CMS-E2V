<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'billing_document')) {
                    $table->string('billing_document')->nullable()->after('city');
                }
                if (!Schema::hasColumn('users', 'billing_complement')) {
                    $table->string('billing_complement')->nullable()->after('billing_document');
                }
                if (!Schema::hasColumn('users', 'billing_razon_social')) {
                    $table->string('billing_razon_social')->nullable()->after('billing_complement');
                }
            });
        }

        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('wallet_transactions', 'invoice_number')) {
                    $table->string('invoice_number')->nullable()->after('external_payment_id');
                }
                if (!Schema::hasColumn('wallet_transactions', 'invoice_url')) {
                    $table->string('invoice_url')->nullable()->after('invoice_number');
                }
            });
        }
    }

    public function down(): void
    {
        // no-op for safety
    }
};
