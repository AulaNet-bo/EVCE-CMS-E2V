<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\LibelulaPaymentService;

try {
    $service = app(LibelulaPaymentService::class);
    // Use reflection to call private markCompleted if necessary, 
    // but handleWebhook is public and safer.
    
    echo "Crediting ID 88...\n";
    $service->handleWebhook(['transaction_id' => 'LBE-88-20260425134833', 'status' => 'COMPLETED']);
    
    echo "Crediting ID 89...\n";
    $service->handleWebhook(['transaction_id' => 'LBE-89-20260425135039', 'status' => 'COMPLETED']);
    
    echo "DONE.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
