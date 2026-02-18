<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wallet_transactions')) {
            return;
        }

        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('wallet_transactions', 'status')) {
                $table->string('status')->nullable()->default('PENDING')->after('amount');
            }
            if (!Schema::hasColumn('wallet_transactions', 'reference_id')) {
                $table->string('reference_id')->nullable()->after('reference');
            }
            if (!Schema::hasColumn('wallet_transactions', 'external_payment_id')) {
                $table->string('external_payment_id')->nullable()->after('reference_id');
            }
            if (!Schema::hasColumn('wallet_transactions', 'balance_after')) {
                $table->decimal('balance_after', 12, 4)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('wallet_transactions', 'currency')) {
                $table->string('currency', 8)->nullable()->after('balance_after');
            }
            if (!Schema::hasColumn('wallet_transactions', 'metadata')) {
                $table->json('metadata')->nullable()->after('description');
            }
        });

        if (Schema::hasColumn('wallet_transactions', 'status')) {
            DB::table('wallet_transactions')->whereNull('status')->update(['status' => 'PENDING']);
        }
        if (Schema::hasColumn('wallet_transactions', 'reference') && Schema::hasColumn('wallet_transactions', 'reference_id')) {
            DB::statement('UPDATE wallet_transactions SET reference_id = reference WHERE reference_id IS NULL AND reference IS NOT NULL');
        }
        if (Schema::hasColumn('wallet_transactions', 'status') && Schema::hasColumn('wallet_transactions', 'reference')) {
            DB::statement("UPDATE wallet_transactions SET status='COMPLETED' WHERE type='CHARGE' AND (status IS NULL OR status='PENDING')");
            DB::statement("UPDATE wallet_transactions SET status='COMPLETED' WHERE reference LIKE 'APP-TOPUP-%' AND (status IS NULL OR status='PENDING')");
        }
    }

    public function down(): void
    {
        // no-op for safety on legacy environments
    }
};
