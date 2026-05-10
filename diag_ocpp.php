<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Cookie\CookieJar;

$chargeBoxId = "SIMULADOR01";
$idTag = "E2V-TEST-TAG";
$connectorId = 1;

echo "--- DIAGNOSTIC: RemoteStartTransaction ---\n";

$baseUrl = "http://steve-ocpp-local:8180/steve";
$user = "mgr_api";
$pass = "Mgr#742913";

$signinUrl = $baseUrl . '/manager/signin';
$jar = new CookieJar();

echo "1. Attempting sign-in to $signinUrl...\n";
$resp = Http::withOptions(['cookies' => $jar])->get($signinUrl);
if (!$resp->ok()) {
    die("FAILED to load signin page: " . $resp->status() . "\n");
}

if (!preg_match('/name="_csrf" value="([^"]+)"/', $resp->body(), $m)) {
    die("FAILED to find CSRF token\n");
}
$csrf = $m[1];
echo "Found CSRF: $csrf\n";

$loginResp = Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])
    ->asForm()
    ->post($signinUrl, [
        'username' => $user,
        'password' => $pass,
        '_csrf' => $csrf,
    ]);

echo "Login status: " . $loginResp->status() . "\n";
if (!in_array($loginResp->status(), [302, 303])) {
    die("Login failed. Check credentials!\n");
}

echo "2. Resolving Charge Point Identifier...\n";
// Manually building based on DB observation
$identifier = "V_16_JSON;SIMULADOR01;-";
echo "Using identifier: $identifier\n";

$opUrl = $baseUrl . '/manager/operations/v1.6/RemoteStartTransaction';
echo "3. GET operation form at $opUrl...\n";
$formResp = Http::withOptions(['cookies' => $jar])->get($opUrl);
if (!$formResp->ok()) {
    die("Failed to load operation form\n");
}

if (preg_match('/name="_csrf" value="([^"]+)"/', $formResp->body(), $fm)) {
    $csrf = $fm[1];
}
echo "Effective CSRF: $csrf\n";

echo "4. POST RemoteStartTransaction...\n";
$postResp = Http::withOptions(['cookies' => $jar])
    ->asForm()
    ->post($opUrl, [
        'chargePointSelectList' => $identifier,
        'idTag' => $idTag,
        'connectorId' => $connectorId,
        '_csrf' => $csrf,
    ]);

echo "Response Status: " . $postResp->status() . "\n";
// Look for result message in the HTML body
if (preg_match('/<div class="alert alert-success">([^<]+)<\/div>/', $postResp->body(), $am)) {
    echo "SUCCESS MESSAGE: " . trim($am[1]) . "\n";
} elseif (preg_match('/<div class="alert alert-danger">([^<]+)<\/div>/', $postResp->body(), $bm)) {
    echo "ERROR MESSAGE: " . trim($bm[1]) . "\n";
} else {
    echo "No explicit result message found in body. Examining body snippet...\n";
    echo substr($postResp->body(), 0, 500) . "...\n";
}
