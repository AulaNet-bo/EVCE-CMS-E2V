<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'charge_box_id',
        'location_id',
        'tariff_id',
        'name',
        'model',
        'vendor',
        'serial_number',
        'firmware_version',
        'last_ip',
        'last_heartbeat',
        'is_active',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function connectors(): HasMany
    {
        return $this->hasMany(Connector::class);
    }
}
