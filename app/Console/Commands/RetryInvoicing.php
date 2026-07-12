<?php

namespace App\Console\Commands;

use App\Models\ChargingSession;
use App\Models\WalletTransaction;
use App\Services\BillingService;
use Illuminate\Console\Command;

class RetryInvoicing extends Command
{
    protected $signature = 'billing:retry-invoices {--limit=10} {--session_id=} {--tx_id=}';
    protected $description = 'Retries generating invoices for completed sessions or transactions without invoice_url';

    public function handle(BillingService $billing)
    {
        $limit = (int) $this->option('limit');
        $sessionId = $this->option('session_id');

        if ($sessionId) {
            $sessions = ChargingSession::where('id', $sessionId)->get();
        } else {
            $sessions = ChargingSession::where('status', 'Completed')
                ->whereNull('invoice_url')
                ->latest()
                ->limit($limit)
                ->get();
        }

        $this->info("Found " . $sessions->count() . " sessions without invoice.");

        foreach ($sessions as $session) {
            $this->info("Processing Session #{$session->id} (User: {$session->user_id})...");
            $billing->triggerInvoice($session);
            
            $session->refresh();
            if ($session->invoice_url) {
                $this->info("  ✅ Success: {$session->invoice_url}");
            } else {
                $this->error("  ❌ Failed to generate invoice for Session #{$session->id}");
            }
        }

        // Also check WalletTransactions (Recharges) that might be completed but missing invoice
        // Note: Recharges usually get invoice via Webhook, but we can try to re-verify them
        $txId = $this->option('tx_id');

        if ($txId) {
            $txs = WalletTransaction::where('id', $txId)->get();
        } else {
            $txs = WalletTransaction::where('type', 'RECHARGE')
                ->where('status', 'COMPLETED')
                ->whereNull('invoice_url')
                ->latest()
                ->limit($limit)
                ->get();
        }
            
        $this->info("Found " . $txs->count() . " recharge transactions without invoice.");

        if ($txs->isNotEmpty()) {
            $libelula = app(\App\Services\LibelulaPaymentService::class);
            foreach ($txs as $tx) {
                $this->info("Processing Recharge Transaction #{$tx->id} (User: {$tx->user_id}, Ref: {$tx->reference_id})...");
                $libelula->verifyStatus((int)$tx->id);
                
                $tx->refresh();
                if ($tx->invoice_url) {
                    $this->info("  ✅ Success: {$tx->invoice_url}");
                } else {
                    $this->error("  ❌ Failed to retrieve invoice for Transaction #{$tx->id}");
                }
            }
        }

        return 0;
    }
}
