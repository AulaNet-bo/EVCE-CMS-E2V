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
                $refCol => $request->input('reference') ?: ('APP-RECHARGE-' . now()->format('YmdHis')),
                'description' => $request->input('description', 'Recarga desde app móvil'),
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

        // Notify the user
        $user->notify(new \App\Notifications\GeneralNotification(
            'Recarga exitosa',
            "Se ha realizado una recarga de Bs " . number_format($amount, 2) . " en tu billetera.",
            ['type' => 'RECHARGE', 'amount' => $amount]
        ));

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
            'doc_type' => 'nullable|in:CI,NIT',
        ], [
            'amount.min' => 'El monto mínimo para recarga con Libélula es Bs 1.00',
        ]);

        $user = $request->user();

        $docType = $request->input('doc_type') ?: $user->billing_doc_type;
        $complemento = ($docType === 'NIT') ? '' : ($request->input('complemento') ?? $user->billing_complement);

        if ($request->filled('documento') || $request->filled('complemento') || $request->filled('razon_social') || $request->filled('doc_type')) {
            $user->billing_document = $request->input('documento') ?: $user->billing_document;
            $user->billing_doc_type = $docType;
            $user->billing_complement = $complemento;
            $user->billing_razon_social = $request->input('razon_social') ?: $user->billing_razon_social;
            $user->save();
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'currency' => 'BOB', 'is_postpaid' => false, 'credit_limit' => 0]
        );

        $result = $libelula->createPayment(
            $wallet,
            round((float) $request->input('amount'), 2),
            $request->input('description', 'Recarga Wallet desde app móvil'),
            [
                'razon_social' => $request->input('razon_social') ?: $user->billing_razon_social,
                'documento' => $request->input('documento') ?: $user->billing_document,
                'complemento' => $complemento,
                'doc_type' => $docType,
                'return_url' => url('/payment-return-app?tx_id=' . ($result['transaction_id'] ?? '')),
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
        $status = $statusCol ? (string) ($tx->{$statusCol} ?? 'PENDING') : 'PENDING';

        return response()->json([
            'transaction_id' => (int) $tx->id,
            'status' => strtoupper($status),
            'amount' => (float) ($tx->amount ?? 0),
            'wallet_balance' => (float) ($wallet->fresh()->balance ?? 0),
            'currency' => $wallet->currency ?? 'BOB',
        ]);
    }

    public function deletePending(Request $request, int $transactionId)
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
            return response()->json(['message' => 'Transacción no encontrada'], 404);
        }

        $status = strtoupper((string) ($tx->status ?? 'PENDING'));
        if (!in_array($status, ['PENDING', 'PROCESSING', '-'], true)) {
            return response()->json(['message' => 'Solo se pueden eliminar pendientes'], 422);
        }

        DB::table('wallet_transactions')->where('id', $transactionId)->delete();

        return response()->json(['message' => 'Pendiente eliminado']);
    }
}
