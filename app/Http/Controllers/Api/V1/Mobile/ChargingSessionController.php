<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ChargingSession;
use App\Models\RfidTag;
use App\Models\Station;
use App\Services\SteveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChargingSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = ChargingSession::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Enhance active sessions with real-time metrics from SteVe
        $sessions->getCollection()->transform(function ($session) {
            if ($session->status === 'Active' && $session->transaction_id) {
                $session->current_metrics = $this->getSteveLiveMetrics((int) $session->transaction_id);
            }
            return $session;
        });

        return response()->json($sessions);
    }

    /**
     * Fetches latest metrics (Power, Energy, SoC) from SteVe meter_value table.
     */
    private function getSteveLiveMetrics(int $transactionId): array
    {
        try {
            $latestValues = DB::connection('steve')
                ->table('connector_meter_value')
                ->where('transaction_pk', $transactionId)
                ->orderByDesc('value_timestamp')
                ->get();

            if ($latestValues->isEmpty()) {
                return ['power_kw' => 0, 'energy_kwh' => 0, 'soc' => null];
            }

            $metrics = [
                'power_kw' => 0,
                'energy_kwh' => 0,
                'soc' => null,
                'timestamp' => $latestValues->first()->value_timestamp ?? null,
            ];

            foreach ($latestValues as $mv) {
                $m = strtolower($mv->measurand ?? '');
                $v = (float) $mv->value;

                // Power
                if (str_contains($m, 'power.active.import') && $metrics['power_kw'] == 0) {
                    $metrics['power_kw'] = round($mv->unit === 'W' ? $v / 1000 : $v, 2);
                }
                // Energy (Total so far)
                if (str_contains($m, 'energy.active.import.register') && $metrics['energy_kwh'] == 0) {
                    $metrics['energy_kwh'] = round($mv->unit === 'Wh' ? $v / 1000 : $v, 2);
                }
                // SoC (Battery %)
                if (str_contains($m, 'soc') && $metrics['soc'] === null) {
                    $metrics['soc'] = (int) $v;
                }

                // Stop if we have all
                if ($metrics['power_kw'] > 0 && $metrics['energy_kwh'] > 0 && $metrics['soc'] !== null) break;
            }

            return $metrics;
        } catch (\Throwable $e) {
            Log::error('Error fetching SteVe metrics', ['tx' => $transactionId, 'error' => $e->getMessage()]);
            return ['power_kw' => 0, 'energy_kwh' => 0, 'soc' => null, 'error' => true];
        }
    }

    public function start(Request $request, Station $station)
    {
        $request->validate([
            'connector_id' => 'nullable|integer|min:1',
        ]);

        $user = $request->user();
        $tag = RfidTag::where('user_id', $user->id)->where('is_active', true)->latest('id')->first();

        if (!$tag) {
            return response()->json([
                'message' => 'No tienes RFID tag activo. Contacta al administrador.',
                'status' => 'error',
            ], 422);
        }

        $connectorId = (int) ($request->input('connector_id') ?: ($station->connectors()->orderBy('connector_id')->value('connector_id') ?? 1));

        // 1. Dynamic Balance Validation (v3.0)
        // User requirements: Start credit must cover Session Fee + 5kWh of current block energy.
        $tariff = \App\Models\Tariff::resolveForStation($station);
        $currentPrices = $tariff ? $tariff->getCurrentPrices() : ['price_session' => 0, 'price_kwh' => 0, 'currency' => 'USD'];
        
        $sessionFee = (float) $currentPrices['price_session'];
        $rateKwh = (float) $currentPrices['price_kwh'];
        $safetyKwh = 5.0;
        $minRequired = $sessionFee + ($safetyKwh * $rateKwh);
        
        if ($user->balance < $minRequired) {
            $currency = $currentPrices['currency'] ?? 'USD';
            return response()->json([
                'message' => "Saldo insuficiente para iniciar.",
                'detail' => "Se requiere un saldo mínimo de {$currency} " . number_format($minRequired, 2) . " para cubrir el cargo de inicio (\${$sessionFee}) y un respaldo de {$safetyKwh}kWh (\$" . ($safetyKwh * $rateKwh) . ").",
                'status' => 'insufficient_balance',
                'balance' => (float) $user->balance,
                'required' => $minRequired,
            ], 402);
        }

        $connectorId = (int) ($request->input('connector_id') ?: ($station->connectors()->orderBy('connector_id')->value('connector_id') ?? 1));

        $steve = app(SteveService::class);
        $result = $steve->remoteStart($station->charge_box_id, $connectorId, $tag->tag_code, $user->id);
        
        if (!$result['ok']) {
            return response()->json([
                'message' => 'No se pudo iniciar la carga en SteVe',
                'detail' => $result['detail'],
                'status' => 'error',
            ], 502);
        }

        // 2. Create Transition Session (Starting)
        // This record signals the App to show the "Iniciando" card
        try {
            ChargingSession::create([
                'user_id' => $user->id,
                'status' => 'Starting',
                'station_id' => $station->id,
                'connector_id' => $connectorId,
                'rfid_tag_id' => $tag->id,
                'tariff_id' => $station->tariff_id,
                'start_time' => now(),
                'total_energy_kwh' => 0.0,
                'total_cost' => 0.0,
                'currency' => $station->tariff->currency ?? 'USD',
            ]);
        } catch (\Throwable $e) {
            Log::error('Transition session creation failed', ['error' => $e->getMessage()]);
        }

        // Notify the user
        try {
            $user->notify(new \App\Notifications\GeneralNotification(
                'Carga solicitada',
                "Se ha enviado la orden de inicio a la estación {$station->name} (Conector {$connectorId}).",
                ['type' => 'CHARGING_START', 'station_id' => $station->id]
            ));
        } catch (\Throwable $e) {
            Log::error('Notification error in Charging start', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Solicitud de inicio enviada al cargador',
            'station' => $station->name,
            'status' => 'requested',
            'charge_box_id' => $station->charge_box_id,
            'connector_id' => $connectorId,
            'tag' => $tag->tag_code,
        ]);
    }

    public function stop(Request $request, Station $station)
    {
        $request->validate([
            'transaction_id' => 'nullable|integer|min:1',
        ]);

        $txId = $request->input('transaction_id');

        if (!$txId) {
            $active = DB::connection('steve')
                ->table('transaction as t')
                ->join('connector as c', 'c.connector_pk', '=', 't.connector_pk')
                ->where('c.charge_box_id', $station->charge_box_id)
                ->whereNull('t.stop_timestamp')
                ->orderByDesc('t.start_timestamp')
                ->select('t.transaction_pk')
                ->first();

            $txId = $active->transaction_pk ?? null;
        }

        if (!$txId) {
            return response()->json([
                'message' => 'No se encontró transacción activa para esta estación',
                'status' => 'idle',
            ], 404);
        }

        $steve = app(SteveService::class);
        $result = $steve->remoteStop($station->charge_box_id, (int) $txId, $request->user()->id);
        
        if (!$result['ok']) {
            return response()->json([
                'message' => 'No se pudo detener la carga en SteVe',
                'detail' => $result['detail'],
                'status' => 'error',
            ], 502);
        }

        // Notify the user
        try {
            $request->user()->notify(new \App\Notifications\GeneralNotification(
                'Carga detenida',
                "Se ha enviado la orden de parada a la estación {$station->name}.",
                ['type' => 'CHARGING_STOP', 'station_id' => $station->id]
            ));
        } catch (\Throwable $e) {
            Log::error('Notification error in Charging stop', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Solicitud de parada enviada al cargador',
            'station' => $station->name,
            'status' => 'requested',
            'transaction_id' => (int) $txId,
        ]);
    }

    public function cancel(Request $request, ChargingSession $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($session->status !== 'Starting') {
            return response()->json([
                'message' => 'Solo se pueden cancelar sesiones en estado de inicio.',
                'current_status' => $session->status
            ], 422);
        }

        $session->update([
            'status' => 'Failed',
            'stop_time' => now(),
            'stop_reason' => 'UserCancelled'
        ]);

        return response()->json([
            'message' => 'Solicitud de inicio cancelada exitosamente.',
            'status' => 'cancelled'
        ]);
    }
}
