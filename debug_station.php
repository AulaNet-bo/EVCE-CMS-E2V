<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Station;
use App\Services\SteveDataSource;

$s = Station::find(14);
echo "Station 14: {$s->charge_box_id}\n";

$source = app(SteveDataSource::class);
$tx = $source->getTransactionById(41);
if ($tx) {
    echo "Tx 41 in SteVe:\n";
    echo "Connector PK: {$tx->connector_pk}\n";
    $connector = $source->getConnectorByPk($tx->connector_pk);
    echo "Charge Box ID in SteVe: {$connector->charge_box_id}\n";
}
