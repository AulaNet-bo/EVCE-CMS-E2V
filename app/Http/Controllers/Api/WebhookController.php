<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function libelula(Request $request)
    {
        // Libelula calls this URL with GET?
        // Docs say: GET /registrar_pago?transaction_id={transaction_id}
        
        $transactionId = $request->query('transaction_id');
        $invoiceId = $request->query('invoice_id');
        
        Log::info("Libelula Webhook Received: Tx ID {$transactionId}");

        if (!$transactionId) {
            return response()->json(['error' => 'Missing transaction_id'], 400);
        }

        // Find Transaction by CMS ID (which we sent as 'identificador')
        // In our service we sent: "identificador" => (string) $tx->id
        // So the 'transaction_id' returned by Libelula in query string IS our $tx->id?
        // WAIT. Docs say: "transaction_id: Identificador de la deuda, retornado a su empresa en los parámetros de salida del servicio REGISTRAR DEUDA."
        // Ah, Libelula generates ITS OWN ID? No, "identificador" is OURS.
        // Let's re-read carefully: 
        // "Sintaxis: /registrar_pago?transaction_id={transaction_id}"
        // "transaction_id: Identificador de la deuda... ver parámetros de salida... id_transaccion"
        // So Libelula sends back the LIBELULA ID, not our ID? 
        // Or sends our ID? Usually gateways send OUR reference.
        // Let's assume it sends the ID we need to verify.
        
        // Strategy: Verify status with Libelula API to be sure.
        // Or trust the callback? GET callback is insecure for trusting payment.
        // Better to query Libelula API to confirm status.
        
        // For now, let's just log it. Real implementation should verify.
        
        return response()->json(['message' => 'Received']);
    }
}
