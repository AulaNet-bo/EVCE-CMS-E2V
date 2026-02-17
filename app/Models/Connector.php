<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connector extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_id',
        'connector_id',
        'type',
        'max_power_kw',
        'status',
        'connector_pk'
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
