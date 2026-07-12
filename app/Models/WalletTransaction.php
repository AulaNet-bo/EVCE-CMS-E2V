<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WalletTransaction extends Model
{
    use HasFactory, LogsActivity;
 
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
           ->logOnly(['status', 'amount', 'balance_after', 'payment_method'])
           ->logOnlyDirty()
           ->dontSubmitEmptyLogs();
    }

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

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChargingSession::class, 'reference_id', 'transaction_id');
    }

    /**
     * Prepare a date for array / JSON serialization.
     * Force La Paz time so the mobile app reads it correctly without doing conversions.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return \Carbon\Carbon::instance($date)->setTimezone('America/La_Paz')->format('Y-m-d H:i:s');
    }
}
