<?php

namespace App\Models\Steve;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class ConnectorStatus extends Model
{
    use Sushi;

    protected $primaryKey = 'connector_pk';
    public $timestamps = false;

    protected array $schema = [
        'connector_pk' => 'integer',
        'status' => 'string',
        'status_timestamp' => 'datetime',
        'error_code' => 'string',
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
            // We use the already populated $c->status from SteveDataSource
            $rows[] = [
                'connector_pk' => $c->connector_pk,
                'status' => $c->status ?? 'Unknown',
                'status_timestamp' => date('Y-m-d H:i:s'),
                'error_code' => 'NoError',
            ];
        }
        return $rows;
    }

    protected function sushiShouldCache()
    {
        return false;
    }

    public function connector()
    {
        return $this->belongsTo(Connector::class, 'connector_pk', 'connector_pk');
    }
}
