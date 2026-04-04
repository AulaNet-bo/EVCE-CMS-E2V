<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LibelulaPaymentService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $settings = \App\Models\SystemSetting::get();
        
        $this->baseUrl = rtrim($settings->libelula_api_url ?: env('LIBELULA_API_URL', 'https://api.libelula.bo/rest'), '/');
        $this->apiKey = (string) ($settings->libelula_app_key ?: env('LIBELULA_APP_KEY', ''));
    }

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    public function createPayment(Wallet $wallet, float $amount, string $description = 'Recarga Wallet', array $invoiceData = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'LIBELULA_APP_KEY no configurada',
            ];
        }

        $amount = round($amount, 2);
        $user = $wallet->user;

        $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';
        $statusCol = Schema::hasColumn('wallet_transactions', 'status') ? 'status' : null;
        $currencyCol = Schema::hasColumn('wallet_transactions', 'currency');
        $balanceAfterCol = Schema::hasColumn('wallet_transactions', 'balance_after');

        $txId = DB::transaction(function () use ($wallet, $user, $amount, $description, $refCol, $statusCol, $currencyCol, $balanceAfterCol, $invoiceData) {
            $insert = [
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'RECHARGE',
                'amount' => $amount,
                $refCol => 'LIBELULA-PENDING-' . now()->format('YmdHis') . '-' . random_int(1000, 9999),
                'description' => $description,
                'payment_method' => 'LIBELULA',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('wallet_transactions', 'user_id')) {
                $insert['user_id'] = $user->id;
            }
            if ($currencyCol) {
                $insert['currency'] = $wallet->currency ?? 'BOB';
            }
            if ($balanceAfterCol) {
                $insert['balance_after'] = (float) $wallet->balance;
            }
            if ($statusCol) {
                $insert[$statusCol] = 'PENDING';
            }

            return DB::table('wallet_transactions')->insertGetId($insert);
        });

        $localReference = 'LBE-' . $txId . '-' . now()->format('YmdHis');

        DB::table('wallet_transactions')->where('id', $txId)->update([$refCol => $localReference]);

        $invoicingPolicy = \App\Models\SystemSetting::get()->invoicing_policy ?: 'recharge';
        $canInvoice = ($invoicingPolicy === 'recharge');

        $returnUrl = $invoiceData['return_url'] ?? url('/payment-return-app');
        if (str_contains($returnUrl, '?')) {
            $returnUrl .= "&tx_id={$txId}";
        } else {
            $returnUrl .= "?tx_id={$txId}";
        }

        $payload = [
            'appkey' => $this->apiKey,
            'email_cliente' => $user->email,
            'identificador' => $localReference,
            'callback_url' => route('api.webhooks.libelula'),
            'url_retorno' => $returnUrl,
            'descripcion' => $description,
            'nombre_cliente' => ($invoiceData['razon_social'] ?? null) ?: $user->name,
            'moneda' => $wallet->currency ?? 'BOB',
            'monto' => $amount,
            'lineas_detalle_deuda' => [
                [
                    'concepto' => $description,
                    'cantidad' => 1,
                    'costo_unitario' => $amount,
                    'descuento_unitario' => 0,
                    'detalle' => $description,
                ],
            ],
            // Emit invoice when billing fields are present and policy is recharge.
            'emite_factura' => $canInvoice,
        ];

        if (!empty($invoiceData['documento'])) {
            $payload['documento'] = $invoiceData['documento'];
        }
        if (!empty($invoiceData['complemento'])) {
            $payload['complemento'] = $invoiceData['complemento'];
        }

        try {
            $resp = Http::timeout(20)->post("{$this->baseUrl}/deuda/registrar", $payload);
            $data = $resp->json() ?: [];

            if (!$resp->successful() || (int) ($data['error'] ?? 1) !== 0) {
                $msg = $data['mensaje'] ?? ('HTTP ' . $resp->status());
                $this->markFailed($txId, ['error' => $msg, 'response' => $data]);

                return [
                    'success' => false,
                    'message' => 'Libélula rechazó la creación de pago',
                    'detail' => $msg,
                ];
            }

            $update = [
                'updated_at' => now(),
            ];

            $tx = WalletTransaction::find($txId);
            if ($tx) {
                $update = ['updated_at' => now()];
                if (Schema::hasColumn('wallet_transactions', 'external_payment_id')) {
                    $update['external_payment_id'] = $data['id'] ?? $data['id_transaccion'] ?? null;
                }
                if (Schema::hasColumn('wallet_transactions', 'invoice_url')) {
                    $update['invoice_url'] = $data['url_pasarela_pagos'] ?? null;
                }
                $tx->update($update);
            }

            if ($resp->successful() && (int) ($data['error'] ?? 1) === 0) {
                $paymentUrl = $data['url_pasarela_pagos'] ?? null;
                
                // Update the transaction with the payment URL
                DB::table('wallet_transactions')
                    ->where('id', $txId)
                    ->update(['payment_url' => $paymentUrl]);

                return [
                    'success' => true,
                    'transaction_id' => $txId,
                    'payment_url' => $paymentUrl,
                    'qr_image' => $data['qr_generado'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Error inesperado al procesar respuesta de Libélula',
            ];
        } catch (\Throwable $e) {
            $this->markFailed($txId, ['exception' => $e->getMessage()]);
            Log::error('Libelula createPayment exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error de conexión con Libélula',
                'detail' => $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(array $data): void
    {
        $candidateIds = array_filter([
            $data['identificador'] ?? null,
            $data['transaction_id'] ?? null,
            $data['referencia'] ?? null,
        ]);

        $tx = null;
        $refCol = Schema::hasColumn('wallet_transactions', 'reference_id') ? 'reference_id' : 'reference';

        foreach ($candidateIds as $cid) {
            $tx = DB::table('wallet_transactions')->where($refCol, (string) $cid)->first();
            if ($tx) {
                break;
            }
            if (is_numeric($cid)) {
                $tx = DB::table('wallet_transactions')->where('id', (int) $cid)->first();
                if ($tx) {
                    break;
                }
            }
        }

        if (!$tx) {
            Log::warning('Libelula webhook transaction not found', ['payload' => $data]);
            return;
        }

        $status = strtoupper((string) ($data['status'] ?? $data['estado'] ?? ''));
        $paid = in_array($status, ['PAID', 'PAGADO', 'COMPLETED', 'SUCCESS'], true) || (int) ($data['error'] ?? 0) === 0;

        if ($paid) {
            $this->markCompleted((int) $tx->id, $data);
        } else {
            $this->markFailed((int) $tx->id, $data);
        }
    }

    private function markCompleted(int $txId, array $payload): void
    {
        DB::transaction(function () use ($txId, $payload) {
            $tx = DB::table('wallet_transactions')->where('id', $txId)->lockForUpdate()->first();
            if (!$tx) {
                return;
            }

            $statusCol = Schema::hasColumn('wallet_transactions', 'status') ? 'status' : null;
            if ($statusCol && strtoupper((string) $tx->{$statusCol}) === 'COMPLETED') {
                return;
            }

            $wallet = DB::table('wallets')->where('id', $tx->wallet_id)->lockForUpdate()->first();
            if (!$wallet) {
                return;
            }

            $newBalance = round(((float) $wallet->balance) + ((float) $tx->amount), 2);
            DB::table('wallets')->where('id', $wallet->id)->update([
                'balance' => $newBalance,
                'updated_at' => now(),
            ]);

            $method = $payload['payment_method']
                ?? $payload['metodo_pago'] 
                ?? $payload['medio_pago'] 
                ?? $payload['tipo_pago'] 
                ?? $payload['glosa_metodo_pago'] 
                ?? $payload['glosa_pago']
                ?? 'LIBELULA';

            $update = [
                'payment_method' => 'LIBELULA/' . strtoupper((string) $method),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('wallet_transactions', 'balance_after')) {
                $update['balance_after'] = $newBalance;
            }
            if ($statusCol) {
                $update[$statusCol] = 'COMPLETED';
            }
            if (Schema::hasColumn('wallet_transactions', 'external_payment_id')) {
                $update['external_payment_id'] = $payload['id_transaccion'] ?? ($payload['transaction_id'] ?? null);
            }
            if (Schema::hasColumn('wallet_transactions', 'invoice_number')) {
                $update['invoice_number'] = $payload['invoice_number']
                    ?? $payload['nro_factura']
                    ?? $payload['numero_factura']
                    ?? null;
            }
            if (Schema::hasColumn('wallet_transactions', 'invoice_url')) {
                $update['invoice_url'] = $payload['invoice_url']
                    ?? $payload['factura_url']
                    ?? $payload['url_factura']
                    ?? null;
            }
            if (Schema::hasColumn('wallet_transactions', 'metadata')) {
                $update['metadata'] = json_encode(['webhook' => $payload]);
            }

            DB::table('wallet_transactions')->where('id', $txId)->update($update);

            // Notify the user of successful recharge
            try {
                $user = \App\Models\User::find($wallet->user_id);
                if ($user) {
                    $user->notify(new \App\Notifications\GeneralNotification(
                        'Recarga exitosa',
                        "Tu recarga de Bs " . number_format($tx->amount, 2) . " ha sido procesada.",
                        ['type' => 'RECHARGE', 'amount' => $tx->amount, 'transaction_id' => $txId]
                    ));
                }
            } catch (\Throwable $e) {
                Log::error('Notification error in Libelula markCompleted', ['error' => $e->getMessage()]);
            }
        });
    }

    private function markFailed(int $txId, array $payload): void
    {
        $update = ['updated_at' => now()];

        if (Schema::hasColumn('wallet_transactions', 'status')) {
            $update['status'] = 'FAILED';
        }
        if (Schema::hasColumn('wallet_transactions', 'metadata')) {
            $update['metadata'] = json_encode(['error' => $payload]);
        }

        DB::table('wallet_transactions')->where('id', $txId)->update($update);
    }
}
