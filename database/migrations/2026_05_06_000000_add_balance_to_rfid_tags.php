<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_tags', function (Blueprint $table) {
            $table->decimal('balance', 12, 4)->default(0)->after('name');
            $table->string('currency', 3)->default('BOB')->after('balance');
        });
    }

    public function down(): void
    {
        Schema::table('rfid_tags', function (Blueprint $table) {
            $table->dropColumn(['balance', 'currency']);
        });
    }
};
