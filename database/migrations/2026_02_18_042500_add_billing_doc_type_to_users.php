<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'billing_doc_type')) {
                $table->string('billing_doc_type', 10)->nullable()->after('billing_document');
            }
        });
    }

    public function down(): void
    {
        // no-op
    }
};
