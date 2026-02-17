<?php

namespace App\Models\Steve;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Added this line

class Connector extends Model
{
    use HasFactory; // Added this line

    protected $connection = 'steve';
    protected $table = 'connector';
    protected $primaryKey = 'connector_pk';
    public $timestamps = false; // Assuming Steve DB specific

    public function chargeBox()
    {
        return $this->belongsTo(Station::class, 'charge_box_id', 'charge_box_id');
    }

    public function status()
    {
        return $this->hasOne(ConnectorStatus::class, 'connector_pk', 'connector_pk')
            ->latestOfMany('status_timestamp');
    }
}
