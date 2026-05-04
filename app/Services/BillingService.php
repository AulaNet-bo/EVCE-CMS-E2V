<?php

namespace App\Services;

use App\Models\ChargingSession;
use App\Models\Tariff;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BillingService
{
    /**
     * Calculates the cost based on energy consumption and time blocks.
     * If a session spans multiple blocks, it interpolates energy usage based on time.
     */
    public function calculateSessionCost(ChargingSession $session, float $kwh, ?Carbon $stopTime = null): array
    {
        // DEBUG: var_dump($kwh);
        $tariff = $session->tariff ?: Tariff::first();
        $start = $session->start_time;
        $stop = $stopTime ?? Carbon::now();
        $durationSeconds = max(1, (int) $start->diffInSeconds($stop));

        $blocks = $this->getTariffBlocks($tariff);
        $breakdown = [];
        $totalEnergyCost = 0;
        $totalUtilityCost = 0;

        // If energy is 0, we can't interpolate, so we use current block
        if ($kwh <= 0) {
            $current = $tariff->getCurrentPrices();
            $totalEnergyCost = 0;
            $totalUtilityCost = 0;
            $breakdown[] = [
                'block' => $current['block'],
                'energy_kwh' => 0,
                'rate' => $current['price_kwh'],
                'cost' => 0
            ];
        } else {
            // Logic: For each second of the session, determine which block it belongs to
            // For performance, we can find the intersection of the session interval with each block
            foreach ($blocks as $block) {
                $overlapSeconds = $this->getSecondsOverlap($start, $stop, $block['start'], $block['end']);
                if ($overlapSeconds > 0) {
                    $proportion = $overlapSeconds / $durationSeconds;
                    $blockKwh = $kwh * $proportion;
                    $blockPrice = $blockKwh * $block['price_kwh'];
                    $blockCost = $blockKwh * $block['cost_kwh'];

                    $totalEnergyCost += $blockPrice;
                    $totalUtilityCost += $blockCost;

                    $breakdown[] = [
                        'block' => $block['index'],
                        'energy_kwh' => round($blockKwh, 3),
                        'rate' => $block['price_kwh'],
                        'cost' => round($blockPrice, 2),
                        'seconds' => $overlapSeconds
                    ];
                }
            }
        }

        $sessionFee = (float) ($tariff->price_session ?? 0);
        
        // Minimum billing logic (User said "puede ser 1 sin problema")
        // We apply the minimum to the total energy cost if it's below the rate of 1kWh
        $minBillingKwh = 1.0;
        if ($kwh < $minBillingKwh && $kwh > 0) {
            // Adjust the energy cost to reflect at least 1kWh at the first encountered block rate
            $firstRate = $breakdown[0]['rate'] ?? $tariff->b1_price_kwh;
            $totalEnergyCost = $minBillingKwh * $firstRate;
        }

        // Time penalty (only on stop)
        $timeFee = 0;
        if ($stopTime) {
            $durationMin = $stop->diffInMinutes($start);
            $freeMin = (int) ($tariff->free_minutes ?? 0);
            $priceMin = (float) ($tariff->b1_price_min ?? 0); // Simplified: uses B1 price min
            $timeFee = max(0, $durationMin - $freeMin) * $priceMin;
        }

        $total = round($sessionFee + $totalEnergyCost + $timeFee, 2);

        return [
            'total' => $total,
            'session_fee' => $sessionFee,
            'time_fee' => $timeFee,
            'energy_cost' => round($totalEnergyCost, 2),
            'currency' => $tariff->currency ?? 'USD',
            'utility_cost' => round($totalUtilityCost, 2),
            'margin' => round($total - $totalUtilityCost, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Processes the initial session fee if the user has enough balance 
     * to also cover the minimum required energy (5kWh).
     */
    public function processInitialFee(ChargingSession $session): bool
    {
        $wallet = Wallet::where('user_id', $session->user_id)->first();
        if (!$wallet || $wallet->is_postpaid) {
            return true;
        }

        $tariff = $session->tariff ?: Tariff::first();
        $sessionFee = (float) ($tariff->price_session ?? 0);
        
        // We don't debit yet, we just check if it's possible to debit and have enough for 5kWh
        $currentPrices = $tariff->getCurrentPrices();
        $minRequired = $sessionFee + (5.0 * $currentPrices['price_kwh']);

        if ($wallet->balance < $minRequired) {
            return false;
        }

        // Debit the session fee now
        if ($sessionFee > 0) {
            DB::transaction(function () use ($wallet, $session, $sessionFee, $currentPrices) {
                $wallet->decrement('balance', $sessionFee);
                
                $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
                DB::table('wallet_transactions')->insert([
                    'wallet_id' => $wallet->id,
                    'user_id' => $session->user_id,
                    'type' => 'CHARGE',
                    'amount' => -$sessionFee,
                    $refCol => (string) $session->transaction_id,
                    'description' => "Cargo de Inicio (Parking/Session Fee) #" . $session->transaction_id,
                    'currency' => $currentPrices['currency'],
                    'balance_after' => $wallet->balance,
                    'status' => 'COMPLETED',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $session->update(['debited_amount' => $sessionFee]);
            });
        }

        return true;
    }

    private function getTariffBlocks(Tariff $tariff): array
    {
        $blocks = [];
        for ($i = 1; $i <= 4; $i++) {
            if ($tariff->{"b{$i}_start"} && $tariff->{"b{$i}_end"}) {
                $blocks[] = [
                    'index' => $i,
                    'start' => $tariff->{"b{$i}_start"},
                    'end' => $tariff->{"b{$i}_end"},
                    'price_kwh' => (float) $tariff->{"b{$i}_price_kwh"},
                    'cost_kwh' => (float) $tariff->{"b{$i}_cost_kwh"},
                ];
            }
        }
        return $blocks;
    }

    private function getSecondsOverlap(Carbon $start, Carbon $stop, string $blockStart, string $blockEnd): int
    {
        // This handles sessions spanning multiple days by iterating through each day
        $current = $start->copy();
        $totalOverlap = 0;

        while ($current->lt($stop)) {
            $dayStart = $current->copy()->startOfDay();
            $bStart = Carbon::parse($current->format('Y-m-d ') . $blockStart);
            $bEnd = Carbon::parse($current->format('Y-m-d ') . $blockEnd);

            // Handle blocks that wrap around midnight (if any, though usually SteVe uses 00-24)
            if ($bEnd->lt($bStart)) {
                $bEnd->addDay();
            }

            $overlapStart = $current->max($bStart);
            $overlapEnd = $stop->min($bEnd);

            if ($overlapStart->lt($overlapEnd)) {
                $totalOverlap += $overlapStart->diffInSeconds($overlapEnd);
            }

            // Move to next block or next day
            $current = $bEnd->gt($current) ? $bEnd : $current->addDay()->startOfDay();
            if ($current->gt($stop)) break;
        }

        return $totalOverlap;
    }

    /**
     * Handles incremental debiting for active sessions.
     */
    public function processIncrementalDebit(ChargingSession $session, array $pricing): bool
    {
        $wallet = Wallet::where('user_id', $session->user_id)->first();
        if (!$wallet || $wallet->is_postpaid) {
            return true;
        }

        $cost = $pricing['total'];
        $alreadyDebited = (float) ($session->debited_amount ?? 0);
        $delta = $cost - $alreadyDebited;

        if ($delta < 0.01) {
            return true; // No significant change
        }

        if ($wallet->balance < $delta) {
            Log::warning("Insufficient balance for session", ['session' => $session->id, 'balance' => $wallet->balance, 'required' => $delta]);
            return false; // Signal that session should stop
        }

        DB::transaction(function () use ($wallet, $session, $delta, $pricing) {
            $wallet->balance -= $delta;
            $wallet->save();

            $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
            
            $insertion = [
                'wallet_id' => $wallet->id,
                'user_id' => $session->user_id,
                'type' => 'CHARGE',
                'amount' => -$delta,
                $refCol => (string) $session->transaction_id,
                'description' => "Consumo Parcial #" . $session->transaction_id . " (" . round($session->total_energy_kwh, 2) . " kWh)",
                'currency' => $pricing['currency'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('wallet_transactions', 'balance_after')) {
                $insertion['balance_after'] = $wallet->balance;
            }
            if (Schema::hasColumn('wallet_transactions', 'status')) {
                $insertion['status'] = 'COMPLETED';
            }

            DB::table('wallet_transactions')->insert($insertion);

            $session->increment('debited_amount', $delta);
            $session->update([
                'total_cost' => $pricing['total'],
                'energy_cost' => $pricing['energy_cost'],
                'utility_cost' => $pricing['utility_cost'],
                'margin' => $pricing['margin'],
            ]);
        });

        return true;
    }

    /**
     * Finalizes billing when a session stops.
     */
    public function finalizeBilling(ChargingSession $session)
    {
        if ($session->financial_locked_at) {
            return;
        }

        $pricing = $this->calculateSessionCost($session, $session->total_energy_kwh, $session->stop_time);
        
        $wallet = Wallet::where('user_id', $session->user_id)->first();
        if ($wallet) {
            $alreadyDebited = (float) ($session->debited_amount ?? 0);
            $finalDelta = $pricing['total'] - $alreadyDebited;

            if ($finalDelta > 0) {
                // For finalization, we might allow small negative balances or handle it differently
                // but for now, we follow the same logic.
                DB::transaction(function() use ($wallet, $session, $finalDelta, $pricing) {
                    $wallet->balance -= $finalDelta;
                    $wallet->save();

                    $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
                    
                    DB::table('wallet_transactions')->insert([
                        'wallet_id' => $wallet->id,
                        'user_id' => $session->user_id,
                        'type' => 'CHARGE',
                        'amount' => -$finalDelta,
                        $refCol => (string) $session->transaction_id,
                        'description' => "Ajuste Final #" . $session->transaction_id,
                        'currency' => $pricing['currency'],
                        'balance_after' => $wallet->balance,
                        'status' => 'COMPLETED',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $session->increment('debited_amount', $finalDelta);
                });
            }
        }

        $session->update([
            'total_cost' => $pricing['total'],
            'session_fee' => $pricing['session_fee'],
            'time_fee' => $pricing['time_fee'],
            'energy_cost' => $pricing['energy_cost'],
            'utility_cost' => $pricing['utility_cost'],
            'margin' => $pricing['margin'],
            'financial_locked_at' => now(),
            'applied_tariff_snapshot' => array_merge(
                (array)$session->applied_tariff_snapshot, 
                ['billing_breakdown' => $pricing['breakdown']]
            ),
        ]);

        // Trigger invoicing if applicable
        $this->triggerInvoice($session);
    }

    private function triggerInvoice(ChargingSession $session)
    {
        try {
            $libService = app(\App\Services\LibelulaPaymentService::class);
            $wallet = Wallet::where('user_id', $session->user_id)->first();
            
            $libResponse = $libService->createPayment($wallet, $session->total_cost, "Consumo Energía #{$session->transaction_id}", [
                'emite_factura' => true,
                'internal_usage_tx' => true 
            ]);
            
            if ($libResponse['success'] && !empty($libResponse['payment_url'])) {
                $session->update([
                    'invoice_url' => $libResponse['payment_url'],
                    'external_payment_id' => $libResponse['transaction_id'] ?? null
                ]);
            }
        } catch (\Throwable $ex) {
            Log::error("Failed to trigger invoice for Session #{$session->id}", ['error' => $ex->getMessage()]);
        }
    }

}
