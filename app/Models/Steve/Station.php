<?php

namespace App\Models\Steve;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Station extends Model
{
    use Sushi;

    protected $primaryKey = 'charge_box_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected array $schema = [
        'charge_box_id' => 'string',
        'last_heartbeat_timestamp' => 'datetime',
        'description' => 'string',
    ];

    public function getRows()
    {
        $dataSource = app(\App\Services\SteveDataSource::class);
        $allConnectors = $dataSource->getConnectorsWithStatus();

        if (empty($allConnectors)) {
            // Sushi needs at least one row or a schema to infer columns. If empty, return an empty array, 
            // but it's safer to provide a schema in Sushi if empty, or just return an empty array 
            // and let Sushi handle schema via $schema.
            return [];
        }

        $stations = [];
        $unique = [];
        foreach ($allConnectors as $c) {
            $cbid = $c->charge_box_id;
            if (!$cbid)
                continue;

            if (!isset($unique[$cbid])) {
                $unique[$cbid] = true;
                $stations[] = [
                    'charge_box_id' => $cbid,
                    'last_heartbeat_timestamp' => $c->last_heartbeat_timestamp ?? null,
                    'description' => 'Station ' . $cbid,
                ];
            }
        }
        return $stations;
    }

    protected function sushiShouldCache()
    {
        return false; // Never cache since we want live data from Redis
    }

    public function connectors()
    {
        return $this->hasMany(Connector::class, 'charge_box_id', 'charge_box_id');
    }
}
