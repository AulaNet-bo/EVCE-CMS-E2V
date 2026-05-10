<?php

namespace App\Services;

use App\Models\ChargingSession;
use App\Models\Tariff;
use App\Models\Wallet;
use App\Models\Product;
use App\Models\WalletTransaction;
use App\Models\SystemSetting;
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

        // Use the session fee from the session model if it's already been determined (skipped or charged)
        // Otherwise fallback to the tariff price
        $sessionFee = isset($session->session_fee) ? (float)$session->session_fee : (float)($tariff->price_session ?? 0);
        
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
        if ($stopTime && ($tariff->is_time_fee_enabled ?? true)) {
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
        return DB::transaction(function () use ($session) {
            // Refresh and lock session to prevent double processing
            $session = ChargingSession::where('id', $session->id)->lockForUpdate()->first();
            if (!$session) return false;

            $wallet = Wallet::where('user_id', $session->user_id)->first();
            $tag = $session->rfidTag;

            if (!$wallet || $wallet->is_postpaid) {
                return true;
            }

            // 1. Skip logic (Grace Period & Manual Tags)
            if ($this->shouldSkipSessionFee($session)) {
                $session->update([
                    'debited_amount' => 0.0001, // Mark as "processed" with a tiny amount to avoid re-triggering
                    'session_fee' => 0
                ]);
                return true;
            }

            $tariff = $session->tariff ?: Tariff::first();
            $sessionFee = (float) ($tariff->price_session ?? 0);
            
            // We don't debit yet, we just check if it's possible to debit and have enough for 5kWh
            $currentPrices = $tariff->getCurrentPrices();
            $minRequired = $sessionFee + (5.0 * $currentPrices['price_kwh']);

            $isVirtualTag = $tag && $tag->is_virtual;
            $availableBalance = ($tag && !$isVirtualTag) ? (float) $tag->balance : (float) $wallet->balance;

            if ($availableBalance < $minRequired) {
                return false;
            }

            // If we already debited the session fee (or more), don't do it again
            if ((float)$session->debited_amount >= $sessionFee && (float)$session->debited_amount > 0) {
                return true;
            }

            // Debit the session fee now
            if ($sessionFee > 0) {
                $isVirtualTag = $tag && $tag->is_virtual;
                if ($tag && !$isVirtualTag) {
                    $tag->decrement('balance', $sessionFee);
                } else {
                    $wallet->decrement('balance', $sessionFee);
                }
                    
                $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
                DB::table('wallet_transactions')->insert([
                    'wallet_id' => $wallet->id,
                    'user_id' => $session->user_id,
                    'type' => 'CHARGE',
                    'amount' => -$sessionFee,
                    $refCol => (string) $session->transaction_id,
                    'description' => "Cargo de Inicio (Parking/Session Fee) #" . $session->transaction_id . ($session->rfidTag ? " (Tarjeta: {$session->rfidTag->tag_code})" : ""),
                    'currency' => $currentPrices['currency'],
                    'balance_after' => ($tag && !$isVirtualTag) ? $tag->balance : $wallet->balance,
                    'status' => 'COMPLETED',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $session->update([
                    'debited_amount' => $sessionFee,
                    'session_fee' => $sessionFee
                ]);
            } else {
                // If fee is 0, mark as processed anyway
                $session->update(['debited_amount' => 0.0001, 'session_fee' => 0]);
            }

            return true;
        });
    }

    /**
     * Determine if the session fee should be skipped.
     */
    private function shouldSkipSessionFee(ChargingSession $session): bool
    {
        $tag = $session->rfidTag;
        $user = $session->user;

        // 1. Manual RFID Card Skip
        // We assume tags with specific products or physical tags without an assigned user are "manual"
        // Based on user request: "cuando el cobro se haga de una tarjeta rfid manual"
        if ($tag && !$tag->is_virtual) {
            // Check if the tag's product internal code is 'MANUAL-TAG' or similar
            // Or if the user explicitly wants ALL physical tags to be free of parking fee?
            // "cuando el cobro se haga de una tarjeta rfid manual igual no se le debe cobrar fee de parqueo solo el consumo"
            if ($tag->product && (str_contains(strtolower($tag->product->name), 'manual') || $tag->product->internal_code === 'MANUAL-TAG')) {
                return true;
            }
        }

        // 2. Grace Period
        // Check if there was a recently completed session for this user/tag
        $settings = SystemSetting::get();
        $graceMinutes = (int) ($settings->billing_grace_period ?? 3);
        
        $recentSession = ChargingSession::where('id', '!=', $session->id)
            ->where(function($q) use ($session) {
                $q->where('user_id', $session->user_id)
                  ->orWhere('rfid_tag_id', $session->rfid_tag_id);
            })
            ->whereNotNull('stop_time')
            ->where('stop_time', '>=', now()->subMinutes($graceMinutes))
            ->exists();

        if ($recentSession) {
            Log::info("Skipping session fee due to grace period", ['session_id' => $session->id, 'user_id' => $session->user_id]);
            return true;
        }

        return false;
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
        $tag = $session->rfidTag;

        if (!$wallet || $wallet->is_postpaid) {
            return true;
        }

        $cost = $pricing['total'];
        $alreadyDebited = (float) ($session->debited_amount ?? 0);
        $delta = $cost - $alreadyDebited;

        if ($delta < 0.01) {
            return true; // No significant change
        }

        $isVirtualTag = $tag && $tag->is_virtual;
        $availableBalance = ($tag && !$isVirtualTag) ? (float) $tag->balance : (float) $wallet->balance;

        if ($availableBalance < $delta) {
            Log::warning("Insufficient balance for session", ['session' => $session->id, 'balance' => $availableBalance, 'required' => $delta]);
            return false; // Signal that session should stop
        }

        DB::transaction(function () use ($wallet, $session, $delta, $pricing, $tag) {
            $isVirtualTag = $tag && $tag->is_virtual;
            if ($tag && !$isVirtualTag) {
                $tag->balance -= $delta;
                $tag->save();
            } else {
                $wallet->balance -= $delta;
                $wallet->save();
            }

            $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
            
            // Try to find an existing "Consumption" transaction for this session to update it
            // instead of creating many small ones.
            $existingTx = WalletTransaction::where('user_id', $session->user_id)
                ->where('type', 'CHARGE')
                ->where($refCol, (string) $session->transaction_id)
                ->where('description', 'like', 'Consumo%')
                ->first();

            $newBalance = ($tag && !$isVirtualTag) ? $tag->balance : $wallet->balance;

            if ($existingTx) {
                $existingTx->update([
                    'amount' => $existingTx->amount - $delta,
                    'balance_after' => $newBalance,
                    'description' => "Consumo Energía #" . $session->transaction_id . " (" . round($session->total_energy_kwh, 2) . " kWh)" . ($session->rfidTag ? " (Tarjeta: {$session->rfidTag->tag_code})" : ""),
                    'updated_at' => now(),
                ]);
            } else {
                $insertion = [
                    'wallet_id' => $wallet->id,
                    'user_id' => $session->user_id,
                    'type' => 'CHARGE',
                    'amount' => -$delta,
                    $refCol => (string) $session->transaction_id,
                    'description' => "Consumo Energía #" . $session->transaction_id . " (" . round($session->total_energy_kwh, 2) . " kWh)" . ($session->rfidTag ? " (Tarjeta: {$session->rfidTag->tag_code})" : ""),
                    'currency' => $pricing['currency'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('wallet_transactions', 'balance_after')) {
                    $insertion['balance_after'] = $newBalance;
                }
                if (Schema::hasColumn('wallet_transactions', 'status')) {
                    $insertion['status'] = 'COMPLETED';
                }

                DB::table('wallet_transactions')->insert($insertion);
            }

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
        $tag = $session->rfidTag;

        if ($wallet) {
            $alreadyDebited = (float) ($session->debited_amount ?? 0);
            $finalDelta = $pricing['total'] - $alreadyDebited;

            if ($finalDelta > 0) {
                // For finalization, we might allow small negative balances or handle it differently
                // but for now, we follow the same logic.
                DB::transaction(function() use ($wallet, $session, $finalDelta, $pricing, $tag) {
                    $isVirtualTag = $tag && $tag->is_virtual;
                    if ($tag && !$isVirtualTag) {
                        $tag->balance -= $finalDelta;
                        $tag->save();
                    } else {
                        $wallet->balance -= $finalDelta;
                        $wallet->save();
                    }

                    $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
                    $newBalance = ($tag && !$isVirtualTag) ? $tag->balance : $wallet->balance;

                    // Consolidate final delta into existing consumption transaction
                    $existingTx = WalletTransaction::where('user_id', $session->user_id)
                        ->where('type', 'CHARGE')
                        ->where($refCol, (string) $session->transaction_id)
                        ->where('description', 'like', 'Consumo%')
                        ->first();

                    if ($existingTx) {
                        $existingTx->update([
                            'amount' => $existingTx->amount - $finalDelta,
                            'balance_after' => $newBalance,
                            'description' => "Consumo Energía #" . $session->transaction_id . " (" . round($session->total_energy_kwh, 2) . " kWh)" . ($session->rfidTag ? " (Tarjeta: {$session->rfidTag->tag_code})" : ""),
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('wallet_transactions')->insert([
                            'wallet_id' => $wallet->id,
                            'user_id' => $session->user_id,
                            'type' => 'CHARGE',
                            'amount' => -$finalDelta,
                            $refCol => (string) $session->transaction_id,
                            'description' => "Consumo Energía #" . $session->transaction_id . ($session->rfidTag ? " (Tarjeta: {$session->rfidTag->tag_code})" : ""),
                            'currency' => $pricing['currency'],
                            'balance_after' => $newBalance,
                            'status' => 'COMPLETED',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

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
            if (!$wallet) {
                Log::warning("Cannot trigger invoice: User has no wallet", ['user_id' => $session->user_id]);
                return;
            }
            
            $settings = SystemSetting::get();
            $lineItems = [];
            
            // 1. Energy Component
            $energyProduct = Product::find($settings->product_energy_id) 
                ?? $tariff?->energyProduct 
                ?? Product::where('internal_code', 'ENERGY-SVC')->first();

            if ($session->energy_cost > 0) {
                $lineItems[] = [
                    'concepto' => "Consumo de Energía ({$session->total_energy_kwh} kWh)",
                    'cantidad' => 1,
                    'costo_unitario' => round($session->energy_cost, 2),
                    'descuento_unitario' => 0,
                    'detalle' => "Carga en Estación: " . ($session->station?->name ?? 'EV Charger'),
                    'codigo_producto' => $energyProduct?->siat_product_code ?? '1',
                ];
            }

            // 2. Connection Fee (Parking)
            if ($session->session_fee > 0) {
                $connProduct = Product::find($settings->product_connection_id)
                    ?? $tariff?->connectionProduct 
                    ?? Product::where('internal_code', 'CONN-FEE')->first();

                $lineItems[] = [
                    'concepto' => "Cargo por Conexión / Inicio de Sesión",
                    'cantidad' => 1,
                    'costo_unitario' => round($session->session_fee, 2),
                    'descuento_unitario' => 0,
                    'detalle' => "Servicio de Conexión",
                    'codigo_producto' => $connProduct?->siat_product_code ?? '5',
                ];
            }

            // 3. Time Penalty Fee
            if ($session->time_fee > 0) {
                $timeProduct = Product::find($settings->product_penalty_id)
                    ?? $tariff?->timeProduct 
                    ?? Product::where('internal_code', 'TIME-PENALTY')->first();

                $lineItems[] = [
                    'concepto' => "Recargo por Tiempo Excedido",
                    'cantidad' => 1,
                    'costo_unitario' => round($session->time_fee, 2),
                    'descuento_unitario' => 0,
                    'detalle' => "Penalty fee por ocupación excesiva",
                    'codigo_producto' => $timeProduct?->siat_product_code ?? '1',
                ];
            }

            // Fallback for empty line items (unlikely but safe)
            if (empty($lineItems)) {
                $lineItems[] = [
                    'concepto' => "Servicio de Carga #" . $session->transaction_id,
                    'cantidad' => 1,
                    'costo_unitario' => round($session->total_cost, 2),
                    'descuento_unitario' => 0,
                    'detalle' => "Consumo de Energía",
                    'codigo_producto' => $energyProduct?->siat_product_code ?? '1',
                ];
            }

            $libResponse = $libService->createPayment($wallet, $session->total_cost, "Consumo Energía #{$session->transaction_id}", [
                'emite_factura' => true,
                'internal_usage_tx' => true,
                'session_id' => $session->id,
                'identificador' => "SES-{$session->transaction_id}",
                'line_items' => $lineItems,
                'codigo_tipo_documento' => $session->user?->billing_doc_type ?? 'CI',
            ], true);
            
            if ($libResponse['success'] && !empty($libResponse['payment_url'])) {
                $session->update([
                    'invoice_url' => $libResponse['payment_url'],
                    'external_payment_id' => $libResponse['transaction_id'] ?? null
                ]);

                // Also update the wallet transaction so it appears in the mobile app history
                $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
                WalletTransaction::where('user_id', $session->user_id)
                    ->where('type', 'CHARGE')
                    ->where($refCol, (string) $session->transaction_id)
                    ->update(['invoice_url' => $libResponse['payment_url']]);
            }
        } catch (\Throwable $ex) {
            Log::error("Failed to trigger invoice for Session #{$session->id}", ['error' => $ex->getMessage()]);
        }
    }

}
