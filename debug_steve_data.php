<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SteveDataSource;
use App\Models\RfidTag;

$source = app(SteveDataSource::class);
$txs = $source->getSessionsForSync(null, 30, 20);

echo "Transactions from SteVe:\n";
foreach ($txs as $s) {
    $tag = $s->id_tag;
    $localTag = RfidTag::where('tag_code', $tag)->first();
    echo "Tx: {$s->transaction_pk} | Tag in SteVe: [$tag] | Found in CMS: " . ($localTag ? 'YES (User ID: ' . $localTag->user_id . ')' : 'NO') . "\n";
}
