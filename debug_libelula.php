<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'https://api.libelula.bo/rest';
$appKey = '11bb10ce-68ba-4af1-8eb7-4e6624fed729';
$localRef = 'LBE-94-20260425144024';

$tests = [
    ['name' => 'JSON POST', 'url' => "$baseUrl/deuda/consultar", 'params' => ['appkey' => $appKey, 'identificador' => $localRef]],
    ['name' => 'JSON POST (app_key variant)', 'url' => "$baseUrl/deuda/consultar", 'params' => ['app_key' => $appKey, 'identificador' => $localRef]],
];

foreach ($tests as $i => $t) {
    echo "TEST #$i: {$t['name']} to {$t['url']}\n";
    try {
        $resp = Http::post($t['url'], $t['params']);
        echo "RESPONSE: " . $resp->status() . " | " . $resp->body() . "\n\n";
    } catch (\Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n\n";
    }
}
