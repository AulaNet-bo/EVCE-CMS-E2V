<?php

namespace App\Models\Steve;

use Illuminate\Database\Eloquent\Model;

class OcppTag extends Model
{
    protected $connection = 'steve';
    protected $table = 'ocpp_tag';
    protected $primaryKey = 'ocpp_tag_pk';
    public $timestamps = false;
}
