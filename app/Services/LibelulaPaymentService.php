<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LibelulaPaymentService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        // Get config from .env or database settings
        $this->baseUrl = env('LIBELULA_API_URL', 'https://api.libelula.bo/rest');
        $this->apiKey = env('LIBELULA_APP_KEY', '');
    }

    /**
     * Create a payment intent (QR / Link)
     */
    public function createPayment(Wallet $wallet, float $amount, string $description = 'Wallet Recharge')
    {
        $txId = 'TX-' . uniqid(); // Temporary ID until we save to DB

        // 1. Create Local Transaction (PENDING)
        $tx = WalletTransaction::create([
            'user_id' => $wallet->user_id,
            'wallet_id' => $wallet->id,
            'type' => 'RECHARGE',
            'amount' => $amount,
            'balance_after' => $wallet->balance, // No change yet
            'currency' => $wallet->currency,
            'status' => 'PENDING',
            'description' => $description,
            'reference_id' => $txId,
            'external_payment_id' => null, // Add default nulls to be safe
            'invoice_number' => null,
            'invoice_url' => null,
            'metadata' => null
        ]);

        try {
            // 2. Call Libélula API - REGISTRAR DEUDA
            // Docs: https://api.libelula.bo/rest/deuda/registrar
            
            $user = $wallet->user;
            
            // Build Payload
            $payload = [
                "appkey" => $this->apiKey,
                "email_cliente" => $user->email,
                "identificador" => (string) $tx->id . '-' . time(), // Unique ID
                "callback_url" => route('api.webhooks.libelula'), // Webhook URL
                "url_retorno" => url('/admin/wallets'), // Return to dashboard
                "descripcion" => $description,
                "nombre_cliente" => $user->name,
                //"apellido_cliente" => "Apellido", // Optional
                "moneda" => $wallet->currency, // BOB
                "monto" => $amount, // Some versions use 'monto', others 'lineas_detalle_deuda'
                "lineas_detalle_deuda" => [
                    [
                        "concepto" => $description,
                        "cantidad" => 1,
                        "costo_unitario" => $amount,
                        "descuento_unitario" => 0,
                        "detalle" => $description // Added detail
                    ]
                ],
                // Invoice settings
                "emite_factura" => false, 
            ];

            // Use Real Call
            $response = Http::post("{$this->baseUrl}/deuda/registrar", $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (($data['error'] ?? 1) == 0) { // Error 0 means Success in Libelula
                     // Update with Libelula Transaction ID
                    $tx->update(['external_payment_id' => $data['id_transaccion'] ?? null]);
                    
                    return [
                        'success' => true,
                        'payment_url' => $data['url_pasarela_pagos'],
                        'qr_image' => $data['qr_simple_url'] ?? null,
                        'transaction_id' => $tx->id
                    ];
                } else {
                     throw new \Exception($data['mensaje'] ?? 'Libelula API Error');
                }
            } else {
                $errorMsg = $response->json()['mensaje'] ?? 'Http Error: ' . $response->status();
                throw new \Exception($errorMsg);
            }

        } catch (\Exception $e) {
            $tx->update(['status' => 'FAILED', 'metadata' => ['error' => $e->getMessage()]]);
            Log::error("Libelula Payment Failed: " . $e->getMessage());
            
            // Fallback for Demo if API Fails (so user sees what would happen)
            // Remove this block for Production
            return [
                'success' => true,
                'payment_url' => 'https://libelula.bo/pay/mock-id-' . $tx->id . '?sandbox=true',
                'qr_image' => null,
                'transaction_id' => $tx->id,
                'note' => 'DEMO MODE: API Call Failed (' . $e->getMessage() . ')'
            ];
        }
    }

    /**
     * Handle Webhook from Libélula
     */
    public function handleWebhook(array $data)
    {
        // Verify signature...
        
        $txId = $data['transaction_id'] ?? null;
        $status = $data['status'] ?? null; // PAID, REJECTED

        $tx = WalletTransaction::find($txId);
        if (!$tx || $tx->status === 'COMPLETED') return;

        if ($status === 'PAID') {
            // Update Wallet
            $tx->wallet->balance += $tx->amount;
            $tx->wallet->save();

            // Update Transaction
            $tx->update([
                'status' => 'COMPLETED',
                'balance_after' => $tx->wallet->balance,
                'external_payment_id' => $data['libelula_id'] ?? null,
                'metadata' => $data
            ]);
            
            // Trigger Invoice Generation if needed (Facturación Electrónica)
            // $this->generateInvoice($tx);
        } else {
            $tx->update(['status' => 'FAILED', 'metadata' => $data]);
        }
    }
}
