<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibelulaApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint',
        'method',
        'request_payload',
        'response_payload',
        'http_status',
        'transaction_id',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(WalletTransaction::class, 'transaction_id');
    }
}
