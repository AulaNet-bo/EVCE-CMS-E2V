<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Cookie\CookieJar;

$chargeBoxId = "SIMULADOR01";
$connectorId = 1;

echo "--- DEEP DIAGNOSTIC: RemoteStartTransaction (w/ Redirect Follow) ---\n";

$baseUrl = "http://steve-ocpp-local:8180/steve";
$user = "mgr_api";
$pass = "Mgr#742913";
$jar = new CookieJar();

// 1. Sign in
echo "1. Sign in...\n";
$signinResp = Http::withOptions(['cookies' => $jar])->get($baseUrl . '/manager/signin');
preg_match('/name="_csrf" value="([^"]+)"/', $signinResp->body(), $m);
$csrf = $m[1] ?? die("No CSRF in signin\n");

$loginResp = Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])
    ->asForm()->post($baseUrl . '/manager/signin', [
        'username' => $user, 'password' => $pass, '_csrf' => $csrf
    ]);

if (!in_array($loginResp->status(), [302, 303])) die("Login failed status: " . $loginResp->status() . "\n");
echo "Login SUCCESS\n";

// 2. Resolve Identifier (What Laravel currently sends)
$identifier = "V_16_JSON;SIMULADOR01;-";
echo "Attempting with Identifier: $identifier\n";

// 3. Post RemoteStart (Following Redirect)
$opUrl = $baseUrl . '/manager/operations/v1.6/RemoteStartTransaction';
$formResp = Http::withOptions(['cookies' => $jar])->get($opUrl);
preg_match('/name="_csrf" value="([^"]+)"/', $formResp->body(), $fm);
$csrf2 = $fm[1] ?? die("No CSRF in form\n");

echo "Submitting form...\n";
$postResp = Http::withOptions(['cookies' => $jar, 'allow_redirects' => true])
    ->asForm()->post($opUrl, [
        'chargePointSelectList' => $identifier,
        'idTag' => 'E2V-TEST-TAG',
        'connectorId' => $connectorId,
        '_csrf' => $csrf2
    ]);

echo "Command status: " . $postResp->status() . "\n";

// 4. PARSE RESULT MESSAGE
if (preg_match('/<div class="alert alert-success">([^<]+)<\/div>/', $postResp->body(), $am)) {
    echo "--- SUCCESS: " . trim(strip_tags($am[1])) . " ---\n";
} elseif (preg_match('/<div class="alert alert-danger">([^<]+)<\/div>/', $postResp->body(), $bm)) {
    echo "--- ABORTED: " . trim(strip_tags($bm[1])) . " ---\n";
} else {
    echo "No alert found. Searching for 'not found' text...\n";
    if (str_contains($postResp->body(), 'Charge point not found')) echo "!!! ERROR: Charge Point Not Found in SteVe !!!\n";
    else echo "Body tail: " . substr(strip_tags($postResp->body()), -500) . "\n";
}
