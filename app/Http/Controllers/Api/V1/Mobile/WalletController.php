<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function balance(Request $request)
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0]
        );

        return response()->json([
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'credit_limit' => $wallet->credit_limit
        ]);
    }

    public function history(Request $request)
    {
        $wallet = Wallet::where('user_id', $request->user()->id)->first();
        
        if (!$wallet) {
            return response()->json([]);
        }

        return response()->json($wallet->transactions()->latest()->paginate(10));
    }
}
