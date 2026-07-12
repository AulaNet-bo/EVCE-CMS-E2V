<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LibelulaPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function libelula(Request $request, LibelulaPaymentService $libelula)
    {
        $payload = array_merge($request->query(), $request->all());

        // Log entry IMMEDIATELY to catch any request
        \App\Models\LibelulaApiLog::create([
            'endpoint' => 'Webhook-Debug',
            'method' => $request->method(),
            'request_payload' => [
                'full_url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
                'payload' => $payload,
                'raw_content' => $request->getContent(),
            ],
            'response_payload' => null,
            'http_status' => 200,
        ]);

        Log::info('Libelula Webhook Received', $payload);

        try {
            $libelula->handleWebhook($payload);
            return response()->json(['message' => 'Webhook processed']);
        } catch (\Throwable $e) {
            Log::error('Libelula webhook error', ['error' => $e->getMessage(), 'payload' => $payload]);
            return response()->json(['message' => 'Webhook error'], 500);
        }
    }
}
