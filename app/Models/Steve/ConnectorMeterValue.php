<?php

namespace App\Models\Steve;

use Illuminate\Database\Eloquent\Model;

class ConnectorMeterValue extends Model
{
    protected $connection = 'steve';
    protected $table = 'connector_meter_value';
    
    // No primary key defined in typical log table, but Eloquent needs one for some features.
    // Usually these are just logs. We treat them read-only.
    public $incrementing = false;
    public $timestamps = false;

    // Casts
    protected $casts = [
        'value_timestamp' => 'datetime',
    ];

    /**
     * Filament needs a unique primary key to render rows.
     * Since this is a log table, we generate a synthetic key.
     */
    public function getKeyName()
    {
        return 'id'; // Return a fake key name
    }

    public function getKey()
    {
        // Generate a synthetic key based on content
        return md5($this->transaction_pk . $this->value_timestamp . $this->measurand . $this->reading_context);
    }
    
    // Support $model->id access
    public function getAttribute($key)
    {
        if ($key === 'id') {
            return $this->getKey();
        }
        return parent::getAttribute($key);
    }

    public function transaction()
    {
        return $this->belongsTo(ChargingSession::class, 'transaction_pk', 'transaction_pk');
    }
}
