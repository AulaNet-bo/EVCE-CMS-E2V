<?php

namespace App\Models\Steve;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Connector extends Model
{
    use Sushi;

    protected $primaryKey = 'connector_pk';
    public $timestamps = false;

    protected array $schema = [
        'connector_pk' => 'integer',
        'charge_box_id' => 'string',
        'connector_id' => 'integer',
    ];

    public function getRows()
    {
        $dataSource = app(\App\Services\SteveDataSource::class);
        $allConnectors = $dataSource->getConnectorsWithStatus();

        if (empty($allConnectors)) {
            return [];
        }

        $rows = [];
        foreach ($allConnectors as $c) {
            $rows[] = [
                'connector_pk' => $c->connector_pk,
                'charge_box_id' => $c->charge_box_id,
                'connector_id' => $c->connector_id,
            ];
        }
        return $rows;
    }

    protected function sushiShouldCache()
    {
        return false;
    }

    public function chargeBox()
    {
        return $this->belongsTo(Station::class, 'charge_box_id', 'charge_box_id');
    }

    public function status()
    {
        return $this->hasOne(ConnectorStatus::class, 'connector_pk', 'connector_pk');
    }
}
