<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'https://api.libelula.bo/rest';
$appKey = '11bb10ce-68ba-4af1-8eb7-4e6624fed729';
$localRef = 'LBE-94-20260425144024';

$variants = [
    ['url' => "$baseUrl/deuda/consultar", 'field' => 'nro_identificador'],
    ['url' => "$baseUrl/deuda/estado", 'field' => 'identificador'],
    ['url' => "$baseUrl/deuda/consultar", 'field' => 'identificador_deuda'],
    ['url' => "$baseUrl/deuda/estado", 'field' => 'nro_identificador'],
];

foreach ($variants as $v) {
    echo "TESTING: {$v['url']} with {$v['field']} = $localRef\n";
    try {
        $resp = Http::post($v['url'], ['appkey' => $appKey, $v['field'] => $localRef]);
        echo "RESP: " . $resp->status() . " | " . $resp->body() . "\n\n";

        $respForm = Http::asForm()->post($v['url'], ['appkey' => $appKey, $v['field'] => $localRef]);
        echo "FORM RESP: " . $respForm->status() . " | " . $respForm->body() . "\n\n";
    } catch (\Exception $e) {
        echo "ERR: " . $e->getMessage() . "\n\n";
    }
}
