<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'https://api.libelula.bo/rest';
$appKey = '11bb10ce-68ba-4af1-8eb7-4e6624fed729';
$localRef = 'LBE-94-20260425144024';

$variants = [
    'id' => $localRef,
    'id_recibo' => $localRef,
    'id_deuda' => $localRef,
    'nro_transaccion' => $localRef,
    'identificador_transaccion' => $localRef,
    'Identificador de deuda' => $localRef, // Desperate try
];

foreach ($variants as $field => $val) {
    echo "TESTING: $field = $val\n";
    try {
        $resp = Http::asForm()->post("$baseUrl/deuda/consultar", ['appkey' => $appKey, $field => $val]);
        echo "RESP: " . $resp->body() . "\n\n";
    } catch (\Exception $e) {
        echo "ERR: " . $e->getMessage() . "\n\n";
    }
}
