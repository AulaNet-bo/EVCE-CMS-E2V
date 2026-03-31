<?php

namespace App\Observers;

use App\Models\WalletTransaction;
use App\Models\Wallet;
use App\Services\FirebaseService;

class WalletTransactionObserver
{
    public function created(WalletTransaction $tx): void
    {
        $this->syncToFirebase($tx);
    }

    public function updated(WalletTransaction $tx): void
    {
        if ($tx->wasChanged(['status', 'amount', 'balance_after'])) {
            $this->syncToFirebase($tx);
        }
    }

    protected function syncToFirebase(WalletTransaction $tx): void
    {
        // Retrieve the fresh wallet balance using the relationship or direct lookup
        $wallet = $tx->wallet ?? Wallet::where('id', $tx->wallet_id)->first();
        if ($wallet && $wallet->user_id) {
            FirebaseService::syncUserBalance($wallet->user_id, (float) $wallet->balance);
        }
    }
}
