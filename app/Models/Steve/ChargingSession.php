<?php

namespace App\Models\Steve;

use Illuminate\Database\Eloquent\Model;

class ChargingSession extends Model
{
    protected $connection = 'steve';
    protected $table = 'transaction';
    protected $primaryKey = 'transaction_pk';
    public $timestamps = false;

    public function connector()
    {
        return $this->belongsTo(Connector::class, 'connector_pk', 'connector_pk');
    }

    // Link idTag string to OcppTag model if exists, or just treat as string
    public function ocppTag()
    {
        return $this->belongsTo(OcppTag::class, 'idTag', 'idTag');
    }

    // Accessors
    public function getStatusAttribute()
    {
        return $this->stop_timestamp ? 'Finished' : 'Active';
    }

    public function getEnergyConsumedAttribute()
    {
        // Check if values exist and are numeric
        $stop = $this->stop_value;
        $start = $this->start_value;

        if (is_numeric($stop) && is_numeric($start)) {
            return round(($stop - $start) / 1000, 2); // Wh to kWh
        }
        
        // For active sessions, maybe calculate current consumption if we have meter values?
        // For now, return 0 if incomplete
        return 0;
    }

    public function getDurationAttribute()
    {
        if ($this->start_timestamp) {
            $end = $this->stop_timestamp ? \Carbon\Carbon::parse($this->stop_timestamp) : now();
            $start = \Carbon\Carbon::parse($this->start_timestamp);
            return $start->diffForHumans($end, true);
        }
        return '-';
    }

    // Relationship with meter values
    public function meterValues()
    {
        return $this->hasMany(ConnectorMeterValue::class, 'transaction_pk', 'transaction_pk')->orderBy('value_timestamp', 'desc');
    }
}
