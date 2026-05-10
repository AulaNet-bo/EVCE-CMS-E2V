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
use App\Services\SteveDataSource;
use App\Services\BillingService;
use App\Services\SteveService;

class MonitorActiveTransactions extends Command
{
    protected $signature = 'steve:monitor-transactions {--daemon}';
    protected $description = 'Monitors active transactions in Steve, calculates cost, and enforces credit limits';

    public function handle(SteveDataSource $source, BillingService $billing, SteveService $steve)
    {
        $isDaemon = $this->option('daemon');

        do {
            $this->info("🔍 Scanning ALL transactions (Active & Recently Completed) in Steve ({$source->source()})...");

            try {
                $recentTxs = collect($source->getTransactionsForMonitoring(20));

                foreach ($recentTxs as $tx) {
                    $this->processTransaction($tx, $source, $billing, $steve);
                }

                $this->cleanupStuckSessions();

            } catch (\Exception $e) {
                $this->error("🔥 Monitor Failed: " . $e->getMessage());
                Log::error("Monitor Failed", ['error' => $e]);
            }

            if ($isDaemon) {
                sleep(2);
            }
        } while ($isDaemon);
    }

    private function processTransaction($tx, SteveDataSource $source, BillingService $billing, SteveService $steve)
    {
        $txId = $tx->transaction_pk ?? $tx->id;
        $tagCode = $tx->id_tag ?? null;
        $startTimestamp = $tx->start_timestamp ?? null;
        $stopTimestamp = $tx->stop_timestamp ?? null;
        $stopValue = $tx->stop_value ?? null;
        $stopReason = $tx->stop_reason ?? null;

        $existingSession = ChargingSession::where('transaction_id', (string)$txId)
            ->where('status', '!=', 'Starting')
            ->first();

        // --- Collision Detection ---
        if ($existingSession) {
            $steveStart = \Carbon\Carbon::parse($startTimestamp);
            $cmsStart = \Carbon\Carbon::parse($existingSession->start_time);
            
            // If the transaction ID is the same but the start date is more than 24 hours apart, 
            // it's a collision from a legacy SteVe database or a reset.
            if (abs($steveStart->diffInHours($cmsStart, false)) > 24) {
                $this->warn("⚠️ Collision detected for Tx #{$txId}. CMS Session ID {$existingSession->id} is from {$cmsStart}, but SteVe reports {$steveStart}. Ignoring old record.");
                $existingSession = null;
            }
        }

        if ($existingSession && $existingSession->status === 'Completed') {
            $this->line("↪️  Tx #{$txId} already completed in CMS.");
            return;
        }

        $this->line("👉 Analyzing Tx #{$txId} (Tag: {$tagCode})");

        // 1. Identify User & Wallet (Case-Insensitive match)
        $tag = RfidTag::whereRaw('LOWER(tag_code) = ?', [strtolower(trim($tagCode))])->first();
        $userId = $tag?->user_id;

        // 2. Determine Consumption (kWh)
        $isCompleted = !is_null($stopTimestamp);
        if ($isCompleted) {
            $currentWh = floatval($stopValue);
        } else {
            $lastEnergyMeter = $source->getLatestEnergyMeterValue((int) $txId);
            $currentWh = $lastEnergyMeter ? floatval($lastEnergyMeter->value) : 0;
        }

        $startWh = floatval($tx->start_value ?? 0);
        $consumedKwh = max(0, ($currentWh - $startWh) / 1000);

        // 3. Resolve Station and Connector
        $connector = $source->getConnectorByPk((int) ($tx->connector_pk ?? 0));
        $chargeBoxId = $connector->charge_box_id ?? 'Unknown';
        $station = Station::where('charge_box_id', $chargeBoxId)->first();
        
        // CMS-side connector lookup
        $cmsConnector = \App\Models\Connector::where('connector_pk', $tx->connector_pk)->first();
        
        // 4. Resolve Applicable Tariff
        $tariff = \App\Models\Tariff::resolveForStation($station, $startTimestamp);

        // 5. Update/Create ChargingSession in CMS
        if (!$existingSession) {
            // Adoption logic for 'Starting' sessions
            // We search by Station AND (RFID Tag OR User) to be more flexible with App-started sessions
            $existingSession = ChargingSession::where('status', 'Starting')
                ->where('station_id', $station->id ?? 0)
                ->where(function($q) use ($tag, $userId) {
                    $q->where('rfid_tag_id', $tag?->id)
                      ->when($userId, fn($qq) => $qq->orWhere('user_id', $userId));
                })
                ->orderByDesc('created_at')
                ->first();
        }

        $session = ChargingSession::updateOrCreate(
            ['id' => $existingSession?->id ?? 0],
            [
                'transaction_id' => (string)$txId,
                'station_id' => $station->id ?? 1,
                'connector_id' => $cmsConnector?->id ?? 1,
                'user_id' => $userId,
                'rfid_tag_id' => $tag?->id,
                'tariff_id' => $tariff?->id,
                'total_energy_kwh' => $consumedKwh,
                'start_time' => $startTimestamp,
                'stop_time' => $stopTimestamp,
                'meter_start' => $startWh,
                'meter_stop' => $currentWh,
                'status' => $isCompleted ? 'Completed' : 'Active',
            ]
        );

        // 5. Initial Fee Processing (If not debited yet)
        if ($session->wasRecentlyCreated || (float)$session->debited_amount == 0) {
            $billing->processInitialFee($session);
        }

        // 6. Calculate Cost and Billing
        $pricing = $billing->calculateSessionCost($session, $consumedKwh, $isCompleted ? \Carbon\Carbon::parse($stopTimestamp) : null);

        if (!$isCompleted) {
            $ok = $billing->processIncrementalDebit($session, $pricing);
            if (!$ok) {
                $this->error("   🛑 INSUFFICIENT FUNDS! Sending RemoteStop...");
                $steve->remoteStop($chargeBoxId, (int)$txId);
                $session->update(['status' => 'CreditStopped', 'stop_reason' => 'CreditLimitExceeded']);
            }
        } else {
            $billing->finalizeBilling($session);
        }

        $session->save();

        // --- Real-time Sync to Firebase ---
        // This ensures the App sees the 'Charging' status instantly (<10s)
        try {
            $firebaseStatus = $isCompleted ? 'AVAILABLE' : 'CHARGING';
            \App\Services\FirebaseService::syncStationData($chargeBoxId, [
                'status' => $firebaseStatus,
                'connectors' => [
                    (string)$cmsConnector?->connector_id => [
                        'status' => $firebaseStatus
                    ]
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error("Firebase sync failed in monitor: " . $e->getMessage());
        }

        $this->line("   ✅ Synced Tx #{$txId}: " . ($isCompleted ? "Completed" : "Active") . " | {$consumedKwh} kWh | \${$pricing['total']}");
    }

    private function cleanupStuckSessions()
    {
        $stuckSessions = ChargingSession::where('status', 'Starting')
            ->where('created_at', '<', now()->subMinutes(2))
            ->get();

        foreach ($stuckSessions as $session) {
            $session->update([
                'status' => 'Failed',
                'stop_time' => now(),
                'stop_reason' => 'TimeoutWaitingForStart'
            ]);

            $user = $session->user;
            if ($user) {
                $stationName = $session->station?->name ?? 'Cargador';
                $connectorId = $session->connector_id ?? '1';
                $user->notify(new \App\Notifications\GeneralNotification(
                    'Carga fallida',
                    "La carga en el dispensador {$stationName} (Conector {$connectorId}) no se pudo iniciar por tiempo de espera agotado. Por favor verifica esto y vuelve a intentarlo.",
                    ['type' => 'CHARGING_FAILED', 'station_id' => $session->station_id]
                ));
            }
            
            $this->warn("🧹 Cleaned up stuck session #{$session->id} for User #{$session->user_id}");
        }
    }
}
