<?php

namespace App\Console\Commands;

use App\Models\RfidTag;
use App\Models\Station;
use App\Models\Tariff;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\ChargingSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use GuzzleHttp\Cookie\CookieJar;
use Carbon\Carbon;
use App\Services\SteveDataSource;

class MonitorActiveTransactions extends Command
{
    protected $signature = 'steve:monitor-transactions';
    protected $description = 'Monitors active transactions in Steve, calculates cost, and enforces credit limits';

    public function handle(SteveDataSource $source)
    {
        $this->info("🔍 Scanning ALL transactions (Active & Recently Completed) in Steve ({$source->source()})...");

        try {
            $recentTxs = collect($source->getTransactionsForMonitoring(20));

            foreach ($recentTxs as $tx) {
                $this->processTransaction($tx, $source);
            }

        } catch (\Exception $e) {
            $this->error("🔥 Monitor Failed: " . $e->getMessage());
            Log::error("Monitor Failed", ['error' => $e]);
        }
    }

    private function processTransaction($tx, SteveDataSource $source)
    {
        $txId = $tx->transaction_pk ?? $tx->id; // Handle schema variations
        $tagCode = $tx->id_tag ?? null;
        $startTimestamp = $tx->start_timestamp ?? null;
        $stopTimestamp = $tx->stop_timestamp ?? null;
        $stopValue = $tx->stop_value ?? null;
        $stopReason = $tx->stop_reason ?? null;
        
        $this->line("👉 Analyzing Tx #{$txId} (Tag: {$tagCode})");

        // 2. Identify User & Wallet in CMS
        $tag = RfidTag::where('tag_code', $tagCode)->first();
        // Allow creating session even if tag/user missing (Guest/Unknown)
        
        $userId = $tag?->user_id;
        $wallet = $userId ? Wallet::firstOrCreate(['user_id' => $userId]) : null;
        
        // 3. Get Meter Values
        // For Completed Txs, Steve has stop_value directly in transaction table
        // For Active Txs, get latest from connector_meter_value
        
        $isCompleted = !is_null($tx->stop_timestamp ?? null);
        
        if ($isCompleted) {
            $currentWh = floatval($stopValue);
        } else {
            // Get Energy (Wh) from meter_values
            $lastEnergyMeter = $source->getLatestEnergyMeterValue((int) $txId);
            $currentWh = $lastEnergyMeter ? floatval($lastEnergyMeter->value) : 0;
        }

        // Get SoC (%) - Always check meter values
        $lastSoCMeter = $source->getLatestMeterValue((int) $txId, 'SoC');
        $currentSoC = $lastSoCMeter ? intval($lastSoCMeter->value) : 0;

        // Calculate Consumption
        $startWh = floatval($tx->start_value ?? 0);
        
        // Energy in kWh (Steve reports Wh)
        $consumedKwh = max(0, ($currentWh - $startWh) / 1000);

        // 5. Calculate Cost
        // Get ChargeBox ID
        $connector = $source->getConnectorByPk((int) ($tx->connector_pk ?? 0));
        $chargeBoxId = $connector->charge_box_id ?? 'Unknown';
        
        $station = Station::where('charge_box_id', $chargeBoxId)->first();
        $tariff = $this->resolveApplicableTariff($station, $startTimestamp);
        
        $cost = 0;
        $utilityCost = 0;
        $rateKwh = 0;
        $currency = 'USD';
        $pricing = ['session_fee' => 0, 'energy_cost' => 0, 'time_fee' => 0, 'total' => 0, 'rate' => 0, 'cost_rate' => 0];

        if ($tariff) {
            $pricing = $this->calculateCost($consumedKwh, $startTimestamp, $tariff, $stopTimestamp);
            $cost = $pricing['total'];
            $rateKwh = $pricing['rate'];
            
            // Utility Cost based on Time Block
            $baseCostPerKwh = $pricing['cost_rate'] ?? 0.10; // Fallback to 0.10 if not set
            $utilityCost = $consumedKwh * $baseCostPerKwh;
            
            $currency = $tariff->currency ?? 'USD';
        }
        
        $margin = $cost - $utilityCost;

        // 6. Enforce Credit Limit & Deduct Balance (Only for Active sessions and known users)
        // Check if balance is sufficient
        $creditBlocked = false;
        if ($wallet) {
             if (!$isCompleted && !$wallet->is_postpaid) {
                // Enforce fee de parqueo first, then energy cost
                $sessionFee = $pricing['session_fee'] ?? 0;
                $energyCost = $pricing['energy_cost'] ?? $cost;
                $requiredNow = $sessionFee + $energyCost;

                if ($wallet->balance < $sessionFee) {
                    $this->error("   🛑 INSUFFICIENT FUNDS (session fee)! Stopping Transaction...");
                    $this->remoteStop($chargeBoxId, $txId);
                    $creditBlocked = true;
                } elseif ($wallet->balance < $requiredNow) {
                    $this->error("   🛑 INSUFFICIENT FUNDS (energy)! Stopping Transaction...");
                    $this->remoteStop($chargeBoxId, $txId);
                    $creditBlocked = true;
                }
             }
             
             // Deduct on completion (idempotent, compatible with legacy/new wallet_transactions schemas)
             if ($isCompleted) {
                 $refCol = $this->walletTxRefColumn();
                 $statusCol = $this->walletTxStatusColumn();

                 $alreadyQ = DB::table('wallet_transactions')
                     ->where('wallet_id', $wallet->id)
                     ->where('type', 'CHARGE')
                     ->where($refCol, (string) $txId);

                 if ($statusCol) {
                     $alreadyQ->where($statusCol, 'COMPLETED');
                 }

                 $alreadyCharged = $alreadyQ->exists();

                 if (!$alreadyCharged) {
                     if ($wallet->balance >= $cost) {
                         $wallet->balance -= $cost;
                         $wallet->save();

                         $insert = [
                             'wallet_id' => $wallet->id,
                             'type' => 'CHARGE',
                             'amount' => -$cost,
                             $refCol => (string) $txId,
                             'description' => "EV Charging Session #{$txId} ({$consumedKwh} kWh)",
                             'created_at' => now(),
                             'updated_at' => now(),
                         ];

                         if (Schema::hasColumn('wallet_transactions', 'user_id')) {
                             $insert['user_id'] = $userId;
                         }
                         if (Schema::hasColumn('wallet_transactions', 'balance_after')) {
                             $insert['balance_after'] = $wallet->balance;
                         }
                         if (Schema::hasColumn('wallet_transactions', 'currency')) {
                             $insert['currency'] = $currency;
                         }
                         if ($statusCol) {
                             $insert[$statusCol] = 'COMPLETED';
                         }

                         DB::table('wallet_transactions')->insert($insert);

                         $this->info("   💰 Deducted {$cost} {$currency} from Wallet User: {$userId} (New Balance: {$wallet->balance})");
                     } else {
                         $creditBlocked = true;
                         $this->warn("   ⚠️ Insufficient funds at completion. No deduction applied.");
                     }
                 }
             }
        }

        // 7. Update Sync Table (ChargingSession)
        $localConnector = \App\Models\Connector::where('station_id', $station->id ?? 0)
            ->where('connector_id', $connector->connector_id ?? 1)
            ->first();

        ChargingSession::updateOrCreate(
            ['transaction_id' => $txId],
            [
                'station_id' => $station->id ?? 1, // Fallback ID if not found
                'connector_id' => $localConnector ? $localConnector->id : 1,
                'user_id' => $userId,
                'rfid_tag_id' => $tag?->id,
                'total_energy_kwh' => $consumedKwh,
                'total_cost' => $cost,
                'utility_cost' => $utilityCost,
                'margin' => $margin,
                'rate_kwh' => $rateKwh,
                'currency' => $currency,
                'status' => $creditBlocked ? 'CreditStopped' : ($isCompleted ? 'Completed' : 'Active'),
                'start_time' => $startTimestamp,
                'stop_time' => $stopTimestamp,
                'meter_start' => $startWh,
                'meter_stop' => $currentWh,
                'stop_reason' => $creditBlocked ? 'CreditStopped' : $stopReason,
                'current_soc' => $currentSoC,
                'updated_at' => now(),
            ]
        );
        
        $this->line("   ✅ Synced Tx #{$txId}: " . ($isCompleted ? "Completed" : "Active") . " | {$consumedKwh} kWh | \${$cost}");
    }

    private function resolveApplicableTariff(?Station $station, ?string $startTimestamp): ?Tariff
    {
        $ts = $startTimestamp ? Carbon::parse($startTimestamp) : Carbon::now();

        $inWindow = function ($q) use ($ts) {
            $q->where(function ($qq) use ($ts) {
                $qq->whereNull('valid_from')->orWhere('valid_from', '<=', $ts);
            })->where(function ($qq) use ($ts) {
                $qq->whereNull('valid_until')->orWhere('valid_until', '>=', $ts);
            });
        };

        if ($station && $station->tariff_id) {
            $tariff = Tariff::where('id', $station->tariff_id)
                ->where($inWindow)
                ->first();
            if ($tariff) {
                return $tariff;
            }
        }

        return Tariff::where($inWindow)
            ->orderByDesc('valid_from')
            ->first() ?? Tariff::first();
    }

    private function calculateCost($kwh, $startTimeStr, $tariff, $stopTimeStr = null)
    {
        // Simple calculation for MVP
        
        $start = Carbon::parse($startTimeStr);
        $stop = $stopTimeStr ? Carbon::parse($stopTimeStr) : Carbon::now();
        
        // Use Start Time for Tariff Block determination (Simplification)
        $timeStr = $start->format('H:i:s');
        
        $priceKwh = $tariff->b1_price_kwh; // Default Sell Price
        $costKwh = $tariff->b1_cost_kwh;   // Default Buy Cost
        
        // Check blocks
        if ($this->isInBlock($timeStr, $tariff->b1_start, $tariff->b1_end)) {
            $priceKwh = $tariff->b1_price_kwh;
            $costKwh = $tariff->b1_cost_kwh;
        } elseif ($tariff->b2_start && $this->isInBlock($timeStr, $tariff->b2_start, $tariff->b2_end)) {
            $priceKwh = $tariff->b2_price_kwh;
            $costKwh = $tariff->b2_cost_kwh;
        } elseif ($tariff->b3_start && $this->isInBlock($timeStr, $tariff->b3_start, $tariff->b3_end)) {
            $priceKwh = $tariff->b3_price_kwh;
            $costKwh = $tariff->b3_cost_kwh;
        } elseif ($tariff->b4_start && $this->isInBlock($timeStr, $tariff->b4_start, $tariff->b4_end)) {
            $priceKwh = $tariff->b4_price_kwh;
            $costKwh = $tariff->b4_cost_kwh;
        }

        // Fee de parqueo (se descuenta primero)
        $sessionFee = (float) ($tariff->price_session ?? 0);

        // Energy cost (kWh)
        $energyCost = $kwh * $priceKwh;

        // Multa por tiempo: solo aplica cuando la carga terminó
        $timeFee = 0;
        if ($stopTimeStr) {
            $durationMin = $stop->diffInMinutes($start);
            $freeMin = (int) ($tariff->free_minutes ?? 0);
            $priceMin = (float) ($tariff->b1_price_min ?? 0);
            $timeFee = max(0, $durationMin - $freeMin) * $priceMin;
        }

        return [
            'total' => round($sessionFee + $energyCost + $timeFee, 2),
            'rate' => $priceKwh,
            'cost_rate' => $costKwh,
            'session_fee' => $sessionFee,
            'time_fee' => $timeFee,
            'energy_cost' => $energyCost,
        ];
    }

    private function isInBlock($current, $start, $end)
    {
        return $current >= $start && $current <= $end;
    }

    private function walletTxRefColumn(): string
    {
        return Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
    }

    private function walletTxStatusColumn(): ?string
    {
        return Schema::hasColumn('wallet_transactions', 'status') ? 'status' : null;
    }

    private function remoteStop($chargeBoxId, $txId)
    {
        $this->alert("   📡 SENDING REMOTE STOP COMMAND TO STEVE FOR CP: {$chargeBoxId} TX: {$txId}");

        try {
            $baseUrl = rtrim(env('STEVE_MANAGER_URL', 'http://127.0.0.1:8081'), '/');
            $user = env('STEVE_MANAGER_USER', 'mgr_api');
            $pass = env('STEVE_MANAGER_PASS', 'Mgr#742913');

            // Load charge box details (ocpp_protocol, endpoint_address)
            $cb = DB::connection('steve')->table('charge_box')
                ->where('charge_box_id', $chargeBoxId)
                ->first();

            $ocppProtocol = $cb->ocpp_protocol ?? 'ocpp1.6J';
            $endpointAddress = $cb->endpoint_address ?? '-';

            // Build selection value as used by SteVe UI
            $ocppToken = str_contains(strtolower($ocppProtocol), '16') ? 'V_16_JSON'
                : (str_contains(strtolower($ocppProtocol), '15') ? 'V_15_JSON'
                : 'V_12_JSON');
            $chargePointSelectValue = $ocppToken . ';' . $chargeBoxId . ';' . ($endpointAddress ?: '-');

            // Use cookie jar to handle CSRF/session
            $jar = new CookieJar();

            // 1) GET signin page to grab CSRF token
            $signinUrl = $baseUrl . '/manager/signin';
            $signinResp = Http::withOptions(['cookies' => $jar])
                ->get($signinUrl);

            if (!$signinResp->ok()) {
                $this->error("   ❌ Failed to load sign-in page: HTTP {$signinResp->status()}");
                return;
            }

            $csrf = $this->extractCsrfToken($signinResp->body());
            if (!$csrf) {
                $this->error('   ❌ CSRF token not found on sign-in page.');
                return;
            }

            // 2) POST login
            $loginResp = Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])
                ->asForm()
                ->post($signinUrl, [
                    'username' => $user,
                    'password' => $pass,
                    '_csrf' => $csrf,
                ]);

            if (!in_array($loginResp->status(), [302, 303])) {
                $this->error("   ❌ Login failed: HTTP {$loginResp->status()}");
                return;
            }

            // 3) Try RemoteStop on v1.6 -> v1.5 -> v1.2
            $paths = [
                '/manager/operations/v1.6/RemoteStopTransaction',
                '/manager/operations/v1.5/RemoteStopTransaction',
                '/manager/operations/v1.2/RemoteStopTransaction',
            ];

            foreach ($paths as $path) {
                $url = $baseUrl . $path;

                // Refresh CSRF (each form page might have its own token)
                $formResp = Http::withOptions(['cookies' => $jar])
                    ->get($url);

                if (!$formResp->ok()) {
                    continue;
                }

                $formCsrf = $this->extractCsrfToken($formResp->body()) ?: $csrf;

                $postResp = Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])
                    ->asForm()
                    ->post($url, [
                        'chargePointSelectList' => $chargePointSelectValue,
                        'transactionId' => (int) $txId,
                        '_csrf' => $formCsrf,
                    ]);

                if (in_array($postResp->status(), [302, 303])) {
                    $this->info("   ✅ RemoteStop sent via {$path}");
                    return;
                }
            }

            $this->error('   ❌ RemoteStop failed on all operation paths.');

        } catch (\Exception $e) {
            $this->error('   ❌ RemoteStop exception: ' . $e->getMessage());
            Log::error('RemoteStop failed', ['error' => $e]);
        }
    }

    private function extractCsrfToken(string $html): ?string
    {
        if (preg_match('/name="_csrf" value="([^"]+)"/', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}
