<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            
            // Type: RECHARGE (Deposit), CHARGE (Consumption), REFUND, ADJUSTMENT
            $table->string('type')->index(); 
            
            // Amount: Positive for Recharge, Negative for Charge/Consumption
            $table->decimal('amount', 10, 4);
            $table->decimal('balance_after', 10, 4);
            $table->string('currency')->default('BOB');
            
            // Status: PENDING, COMPLETED, FAILED
            $table->string('status')->default('PENDING');
            
            // Reference to external systems (Libelula ID, Invoice ID, Charging Session ID)
            $table->string('reference_id')->nullable(); // e.g., Charging Session ID
            $table->string('external_payment_id')->nullable(); // Libelula Transaction ID
            $table->string('invoice_number')->nullable(); // Official Invoice Number (SFE)
            $table->string('invoice_url')->nullable(); // Link to PDF
            
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Store raw response from Gateway
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
