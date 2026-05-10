<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'https://api.libelula.bo/rest';
$appKey = '11bb10ce-68ba-4af1-8eb7-4e6624fed729';
$libelulaId = 'd7594183-2148-4f62-b5aa-74c548253423';

echo "TEST: Querying by Libelula ID: $libelulaId\n";
try {
    $resp = Http::post("$baseUrl/deuda/consultar", [
        'appkey' => $appKey,
        'id_transaccion' => $libelulaId,
        'identificador' => $libelulaId
    ]);
    echo "JSON RESPONSE: " . $resp->status() . " | " . $resp->body() . "\n\n";

    $respForm = Http::asForm()->post("$baseUrl/deuda/consultar", [
        'appkey' => $appKey,
        'id_transaccion' => $libelulaId,
        'identificador' => $libelulaId
    ]);
    echo "FORM RESPONSE: " . $respForm->status() . " | " . $respForm->body() . "\n\n";
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
