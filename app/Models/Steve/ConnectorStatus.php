<?php

namespace App\Models\Steve;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectorStatus extends Model
{
    use HasFactory;

    protected $connection = 'steve';
    protected $table = 'connector_status';
    protected $primaryKey = 'connector_pk'; // Actually shares PK with connector (composite or related)
    public $timestamps = false;

    public function connector()
    {
        return $this->belongsTo(Connector::class, 'connector_pk', 'connector_pk');
    }
}
