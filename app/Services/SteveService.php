<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SteveService
{
    /**
     * Normalizes OCPP status strings to CMS internal values.
     */
    public function normalizeStatus(string $ocppStatus): string
    {
        $status = strtolower($ocppStatus);

        return match ($status) {
            'available' => 'Available',
            'preparing' => 'Preparing',
            'charging' => 'Charging',
            'suspendedevse', 'suspendedev' => 'Suspended',
            'finishing' => 'Finishing',
            'faulted' => 'Faulted',
            'unavailable' => 'Unavailable',
            'reserved' => 'Reserved',
            'offline' => 'Offline',
            default => 'Unknown',
        };
    }

    /**
     * Resolves status considering the last heartbeat timestamp.
     * If heartbeat is older than 2 minutes, marks as Offline.
     */
    public function normalizeStatusWithHeartbeat(string $ocppStatus, ?string $lastHeartbeat): string
    {
        if ($lastHeartbeat) {
            $heartbeat = \Carbon\Carbon::parse($lastHeartbeat);
            if ($heartbeat->diffInMinutes(now()) > 2) {
                return 'Offline';
            }
        }

        return $this->normalizeStatus($ocppStatus);
    }

    /**
     * Sends a RemoteStartTransaction command via SteVe REST API.
     */
    public function remoteStart(string $chargeBoxId, int $connectorId, string $idTag, ?int $operatorId = null): array
    {
        $this->logAction('START_REQUEST', $chargeBoxId, $connectorId, "Tag: {$idTag}", $operatorId);

        try {
            $settings = SystemSetting::get();
            $baseUrl = rtrim($settings->steve_manager_url ?? env('STEVE_MANAGER_URL', 'http://steve-ocpp-local:8180/steve'), '/');
            $user = $settings->steve_manager_user ?? env('STEVE_MANAGER_USER', 'mgr_api');
            $pass = $settings->steve_manager_pass ?? env('STEVE_MANAGER_PASS', 'Admin#2025');

            $url = "{$baseUrl}/api/v1/operations/RemoteStartTransaction";

            $response = Http::withBasicAuth($user, $pass)
                ->timeout(15)
                ->asJson()
                ->post($url, [
                    'chargeBoxIdList' => [$chargeBoxId],
                    'idTag' => $idTag,
                    'connectorId' => (int)$connectorId,
                ]);

            if ($response->successful()) {
                $this->logAction('START_SUCCESS', $chargeBoxId, $connectorId, "Steve Response: " . $response->body(), $operatorId);
                return ['ok' => true, 'detail' => 'Command Accepted by SteVe'];
            }

            $detail = "SteVe API Error ({$response->status()}): " . ($response->json('message') ?? $response->body());
            $this->logAction('START_FAILED', $chargeBoxId, $connectorId, $detail, $operatorId);
            
            return ['ok' => false, 'detail' => $detail];

        } catch (\Throwable $e) {
            $this->logAction('START_ERROR', $chargeBoxId, $connectorId, $e->getMessage(), $operatorId);
            Log::error("SteveService@remoteStart Exception", ['error' => $e->getMessage(), 'cb' => $chargeBoxId]);
            return ['ok' => false, 'detail' => 'Connection Error: ' . $e->getMessage()];
        }
    }

    /**
     * Sends a RemoteStopTransaction command via SteVe REST API.
     */
    public function remoteStop(string $chargeBoxId, int $transactionId, ?int $operatorId = null): array
    {
        // Find connector_id for logging if possible
        $connectorId = 0; // Default if unknown

        $this->logAction('STOP_REQUEST', $chargeBoxId, $connectorId, "Tx: {$transactionId}", $operatorId);

        try {
            $settings = SystemSetting::get();
            $baseUrl = rtrim($settings->steve_manager_url ?? env('STEVE_MANAGER_URL', 'http://steve-ocpp-local:8180/steve'), '/');
            $user = $settings->steve_manager_user ?? env('STEVE_MANAGER_USER', 'mgr_api');
            $pass = $settings->steve_manager_pass ?? env('STEVE_MANAGER_PASS', 'Admin#2025');

            $url = "{$baseUrl}/api/v1/operations/RemoteStopTransaction";

            $response = Http::withBasicAuth($user, $pass)
                ->timeout(15)
                ->asJson()
                ->post($url, [
                    'chargeBoxIdList' => [$chargeBoxId],
                    'transactionId' => (int)$transactionId,
                ]);

            if ($response->successful()) {
                $this->logAction('STOP_SUCCESS', $chargeBoxId, $connectorId, "Steve Response: " . $response->body(), $operatorId);
                return ['ok' => true, 'detail' => 'Command Accepted by SteVe'];
            }

            $detail = "SteVe API Error ({$response->status()}): " . ($response->json('message') ?? $response->body());
            $this->logAction('STOP_FAILED', $chargeBoxId, $connectorId, $detail, $operatorId);
            
            return ['ok' => false, 'detail' => $detail];

        } catch (\Throwable $e) {
            $this->logAction('STOP_ERROR', $chargeBoxId, $connectorId, $e->getMessage(), $operatorId);
            Log::error("SteveService@remoteStop Exception", ['error' => $e->getMessage(), 'cb' => $chargeBoxId]);
            return ['ok' => false, 'detail' => 'Connection Error: ' . $e->getMessage()];
        }
    }

    /**
     * Internal helper to log actions to remote_audit_logs.
     */
    private function logAction(string $action, string $chargeBoxId, int $connectorId, string $details, ?int $operatorId = null)
    {
        try {
            DB::table('remote_audit_logs')->insert([
                'operator_id' => $operatorId,
                'username' => $operatorId ? ($this->getOperatorName($operatorId)) : 'SYSTEM',
                'action' => $action,
                'charge_box_id' => $chargeBoxId,
                'connector_id' => $connectorId,
                'details' => $details,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to write to remote_audit_logs", ['error' => $e->getMessage()]);
        }
    }

    private function getOperatorName(int $id): string
    {
        return DB::table('remote_operators')->where('id', $id)->value('username') ?? "User#{$id}";
    }
}
