<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'tariff_id',
        'start_time',
        'end_time',
        'price_kwh',
        'price_min',
    ];

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }
}
