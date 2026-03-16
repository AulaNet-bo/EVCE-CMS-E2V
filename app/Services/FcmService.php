<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send a push notification to a specific user token.
     * Note: This implementation is a placeholder using Legacy HTTP API.
     * For production, it is recommended to use Google OAuth2 v1 API.
     */
    public static function send($token, $title, $body, $data = [])
    {
        $serverKey = env('FCM_SERVER_KEY');
        if (!$serverKey || !$token)
            return;

        try {
            Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                        'to' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                            'sound' => 'default',
                        ],
                        'data' => array_merge($data, [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ]),
                        'priority' => 'high',
                    ]);
        } catch (\Exception $e) {
            Log::error('FCM Error: ' . $e->getMessage());
        }
    }
}
