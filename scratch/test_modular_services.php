<?php

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChargingSession;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BillingService;
use App\Services\SteveService;
use Carbon\Carbon;

function runTest() {
    $billing = app(BillingService::class);
    $steve = app(SteveService::class);

    echo "--- STARTING MODULAR SERVICES TEST ---\n\n";

    // 1. Test Steve Status Normalization
    echo "1. Testing SteveService Normalization:\n";
    $testCases = [
        ['Preparing', null, 'Preparing'],
        ['Available', Carbon::now()->subMinutes(5)->toDateTimeString(), 'Offline'], // Test heartbeat timeout
        ['Available', Carbon::now()->subMinute()->toDateTimeString(), 'Available'],
        ['SuspendedEV', null, 'Suspended'],
        ['InvalidStatus', null, 'Unknown'],
    ];

    foreach ($testCases as $case) {
        $normalized = $steve->normalizeStatusWithHeartbeat($case[0], $case[1]);
        $status = ($normalized === $case[2]) ? "PASS" : "FAIL (Got: $normalized, Expected: {$case[2]})";
        echo "   - Status: '{$case[0]}', Heartbeat: '" . ($case[1] ?? 'N/A') . "' -> $normalized [$status]\n";
    }

    // 2. Test BillingService Multi-Block
    echo "\n2. Testing BillingService Multi-Block Calculation:\n";
    
    // Create a mock tariff with blocks
    $tariff = new Tariff([
        'name' => 'Test MultiBlock',
        'currency' => 'USD',
        'price_session' => 1.50,
        'b1_start' => '00:00:00', 'b1_end' => '08:00:00', 'b1_price_kwh' => 1.00, 'b1_cost_kwh' => 0.50,
        'b2_start' => '08:00:00', 'b2_end' => '16:00:00', 'b2_price_kwh' => 2.00, 'b2_cost_kwh' => 1.00,
        'b3_start' => '16:00:00', 'b3_end' => '23:59:59', 'b3_price_kwh' => 1.50, 'b3_cost_kwh' => 0.75,
    ]);

    // Mock session spanning B1 and B2 (07:00 to 09:00 -> 2 hours)
    // 50% in B1 (1 hr), 50% in B2 (1 hr)
    $start = Carbon::parse('2026-05-03 07:00:00');
    $stop = Carbon::parse('2026-05-03 09:00:00');
    $session = new ChargingSession([
        'start_time' => $start,
        'total_energy_kwh' => 10.0,
    ]);
    $session->setRelation('tariff', $tariff);

    $pricing = $billing->calculateSessionCost($session, 10.0, $stop);
    
    // Expected: 
    // Energy: (5kWh * 1.00) + (5kWh * 2.00) = 5 + 10 = 15.00
    // Fee: 1.50
    // Total: 16.50
    
    echo "   - Session 07:00 to 09:00 (10kWh):\n";
    echo "     - Total Cost: \${$pricing['total']} " . ($pricing['total'] == 16.50 ? "[PASS]" : "[FAIL: Expected 16.50]") . "\n";
    echo "     - Breakdown:\n";
    foreach ($pricing['breakdown'] as $b) {
        echo "       * Block {$b['block']}: {$b['energy_kwh']} kWh @ \${$b['rate']} = \${$b['cost']}\n";
    }

    // 3. Test BillingService Min Billing (1kWh)
    echo "\n3. Testing Min Billing (1kWh):\n";
    $sessionSmall = new ChargingSession([
        'start_time' => Carbon::parse('2026-05-03 10:00:00'),
        'total_energy_kwh' => 0.2, // Very small charge
    ]);
    $sessionSmall->setRelation('tariff', $tariff);
    
    $pricingSmall = $billing->calculateSessionCost($sessionSmall, 0.2, Carbon::parse('2026-05-03 10:15:00'));
    // Expected: 1kWh @ 2.00 (Block 2) + 1.50 fee = 3.50
    echo "   - Session with 0.2kWh (Min 1kWh):\n";
    echo "     - Total Cost: \${$pricingSmall['total']} " . ($pricingSmall['total'] == 3.50 ? "[PASS]" : "[FAIL: Expected 3.50]") . "\n";

    // 4. Test Initial Fee Logic (5kWh safety)
    echo "\n4. Testing Initial Fee & 5kWh Safety:\n";
    
    // We need a real user/wallet to test this properly if we were in tinker, but we'll mock the logic
    $user = new User(['id' => 999, 'balance' => 10.00]);
    $wallet = new Wallet(['user_id' => 999, 'balance' => 10.00]);
    
    // Min required: 1.50 (fee) + 5 * 2.00 (B2 rate) = 11.50
    // User has 10.00 -> Should fail.
    
    echo "   - User Balance: $10.00 | Min Required: $11.50\n";
    // Since processInitialFee uses DB queries, we can't easily run it here without real DB setup,
    // but the logic in calculateMinBalance can be tested.
    $minRequired = $tariff->calculateMinBalance(5.0);
    echo "     - calculateMinBalance(5.0): \${$minRequired} " . ($minRequired == 11.50 ? "[PASS]" : "[FAIL]") . "\n";

    echo "\n--- TEST COMPLETE ---\n";
}

runTest();
