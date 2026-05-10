<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\LibelulaPaymentService;

try {
    $service = app(LibelulaPaymentService::class);
    $data = [
        'transaction_id' => 'LBE-86-20260424202828',
        'status' => 'COMPLETED',
        'error' => 0,
        'payment_method' => 'FORCED_BY_ADMIN'
    ];
    
    echo "Processing webhook simulation for ID 86...\n";
    $service->handleWebhook($data);
    echo "DONE.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
