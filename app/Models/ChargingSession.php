<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'station_id',
        'connector_id',
        'user_id',
        'rfid_tag_id',
        'tariff_id',
        'start_time',
        'stop_time',
        'meter_start',
        'meter_stop',
        'total_energy_kwh',
        'total_cost',
        'utility_cost',
        'margin',
        'rate_kwh',
        'currency',
        'status',
        'stop_reason',
        'current_soc',
    ];

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    protected $casts = [
        'start_time' => 'datetime',
        'stop_time' => 'datetime',
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rfidTag(): BelongsTo
    {
        return $this->belongsTo(RfidTag::class);
    }
    
    // Relationship to Steve Logs (Cross-Database if configured, or just standard if same DB user)
    // We use transaction_id (CMS) -> transaction_pk (Steve)
    public function meterValues()
    {
        return $this->hasMany(\App\Models\Steve\ConnectorMeterValue::class, 'transaction_pk', 'transaction_id')->orderBy('value_timestamp', 'desc');
    }
}
