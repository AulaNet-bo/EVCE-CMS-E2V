<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemoteControlSystemTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('remote_operators')) {
            Schema::create('remote_operators', function (Blueprint $table) {
                $table->id();
                $table->string('username')->unique();
                $table->string('password');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('remote_audit_logs')) {
            Schema::create('remote_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('operator_id')->nullable();
                $table->string('username');
                $table->string('action');
                $table->string('charge_box_id');
                $table->integer('connector_id');
                $table->text('details')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('remote_audit_logs');
        Schema::dropIfExists('remote_operators');
    }
}
