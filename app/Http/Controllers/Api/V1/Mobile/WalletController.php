<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\LibelulaPaymentService;

class WalletController extends Controller
{
    public function balance(Request $request)
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0, 'currency' => 'BOB', 'is_postpaid' => false, 'credit_limit' => 0]
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
            return response()->json([
                'data' => [],
                'total' => 0,
            ]);
        }

        return response()->json($wallet->transactions()->latest()->paginate(10));
    }

    public function topup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $amount = round((float) $request->input('amount'), 2);

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'currency' => 'BOB', 'is_postpaid' => false, 'credit_limit' => 0]
        );

        DB::transaction(function () use ($wallet, $user, $amount, $request) {
            $wallet->balance = round(((float) $wallet->balance) + $amount, 2);
            $wallet->save();

            $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
            $statusCol = Schema::hasColumn('wallet_transactions', 'status') ? 'status' : null;

            $insert = [
                'wallet_id' => $wallet->id,
                'type' => 'RECHARGE',
                'amount' => $amount,
                $refCol => $request->input('reference') ?: ('APP-TOPUP-' . now()->format('YmdHis')),
                'description' => $request->input('description', 'Top-up desde app móvil'),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('wallet_transactions', 'user_id')) {
                $insert['user_id'] = $user->id;
            }
            if (Schema::hasColumn('wallet_transactions', 'balance_after')) {
                $insert['balance_after'] = $wallet->balance;
            }
            if (Schema::hasColumn('wallet_transactions', 'currency')) {
                $insert['currency'] = $wallet->currency ?? 'BOB';
            }
            if ($statusCol) {
                $insert[$statusCol] = 'COMPLETED';
            }

            DB::table('wallet_transactions')->insert($insert);
        });

        return response()->json([
            'message' => 'Top-up aplicado correctamente',
            'wallet' => [
                'balance' => $wallet->fresh()->balance,
                'currency' => $wallet->currency,
                'credit_limit' => $wallet->credit_limit,
            ],
        ]);
    }

    public function libelulaCheckout(Request $request, LibelulaPaymentService $libelula)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
            'description' => 'nullable|string|max:255',
            'razon_social' => 'nullable|string|max:255',
            'documento' => 'nullable|string|max:50',
            'complemento' => 'nullable|string|max:50',
        ]);

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0, 'currency' => 'BOB', 'is_postpaid' => false, 'credit_limit' => 0]
        );

        $result = $libelula->createPayment(
            $wallet,
            round((float)$request->input('amount'), 2),
            $request->input('description', 'Recarga Wallet desde app móvil'),
            [
                'razon_social' => $request->input('razon_social'),
                'documento' => $request->input('documento'),
                'complemento' => $request->input('complemento'),
            ]
        );

        if (!($result['success'] ?? false)) {
            return response()->json([
                'message' => $result['message'] ?? 'No se pudo crear el pago',
                'detail' => $result['detail'] ?? null,
            ], 422);
        }

        return response()->json([
            'message' => 'Pago Libélula creado',
            'transaction_id' => $result['transaction_id'] ?? null,
            'payment_url' => $result['payment_url'] ?? null,
            'qr_image' => $result['qr_image'] ?? null,
        ]);
    }

    public function libelulaStatus(Request $request, int $transactionId)
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0, 'currency' => 'BOB', 'is_postpaid' => false, 'credit_limit' => 0]
        );

        $tx = DB::table('wallet_transactions')
            ->where('id', $transactionId)
            ->where('wallet_id', $wallet->id)
            ->first();

        if (!$tx) {
            return response()->json([
                'message' => 'Transacción no encontrada',
            ], 404);
        }

        $statusCol = Schema::hasColumn('wallet_transactions', 'status') ? 'status' : null;
        $status = $statusCol ? (string)($tx->{$statusCol} ?? 'PENDING') : 'PENDING';

        return response()->json([
            'transaction_id' => (int) $tx->id,
            'status' => strtoupper($status),
            'amount' => (float) ($tx->amount ?? 0),
            'wallet_balance' => (float) ($wallet->fresh()->balance ?? 0),
            'currency' => $wallet->currency ?? 'BOB',
        ]);
    }
}
