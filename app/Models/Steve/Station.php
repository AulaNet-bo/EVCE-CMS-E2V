<?php

namespace App\Models\Steve;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $connection = 'steve';
    protected $table = 'charge_box';
    protected $primaryKey = 'charge_box_pk';
    public $timestamps = false;

    public function connectors()
    {
        return $this->hasMany(Connector::class, 'charge_box_id', 'charge_box_id');
    }
}
