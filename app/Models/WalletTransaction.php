<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'type', // RECHARGE, CHARGE, REFUND
        'amount',
        'balance_after',
        'currency',
        'status', // PENDING, COMPLETED, FAILED
        'reference_id', // Charging Session ID or custom ref
        'external_payment_id', // Libelula Transaction ID
        'invoice_number',
        'invoice_url',
        'description',
        'bank_receipt_number',
        'pos_correlative',
        'payment_method',
        'payment_evidence_path',
        'sap_synced_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
