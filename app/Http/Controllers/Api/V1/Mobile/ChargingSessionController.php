<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ChargingSession;
use App\Models\RfidTag;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Cookie\CookieJar;

class ChargingSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = ChargingSession::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($sessions);
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

        $result = $this->remoteStart($station->charge_box_id, $tag->tag_code, $connectorId);
        if (!$result['ok']) {
            return response()->json([
                'message' => 'No se pudo iniciar la carga en SteVe',
                'detail' => $result['detail'],
                'status' => 'error',
            ], 502);
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
                ->join('connector as c', 'c.connector_pk', '=', 't.connector_connector_pk')
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

        $result = $this->remoteStop($station->charge_box_id, (int) $txId);
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

    private function remoteStart(string $chargeBoxId, string $idTag, int $connectorId): array
    {
        try {
            [$jar, $csrf, $baseUrl, $chargePointSelectValue] = $this->loginAndResolveChargePoint($chargeBoxId);

            $paths = [
                '/manager/operations/v1.6/RemoteStartTransaction',
                '/manager/operations/v1.5/RemoteStartTransaction',
                '/manager/operations/v1.2/RemoteStartTransaction',
            ];

            foreach ($paths as $path) {
                $url = $baseUrl . $path;
                $formResp = Http::withOptions(['cookies' => $jar])->get($url);
                if (!$formResp->ok()) {
                    continue;
                }

                $formCsrf = $this->extractCsrfToken($formResp->body()) ?: $csrf;

                $postResp = Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])
                    ->asForm()
                    ->post($url, [
                        'chargePointSelectList' => $chargePointSelectValue,
                        'idTag' => $idTag,
                        'connectorId' => $connectorId,
                        '_csrf' => $formCsrf,
                    ]);

                if (in_array($postResp->status(), [302, 303], true)) {
                    return ['ok' => true, 'detail' => $path];
                }
            }

            return ['ok' => false, 'detail' => 'RemoteStart failed on all operation paths'];
        } catch (\Throwable $e) {
            Log::error('RemoteStart exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'detail' => $e->getMessage()];
        }
    }

    private function remoteStop(string $chargeBoxId, int $txId): array
    {
        try {
            [$jar, $csrf, $baseUrl, $chargePointSelectValue] = $this->loginAndResolveChargePoint($chargeBoxId);

            $paths = [
                '/manager/operations/v1.6/RemoteStopTransaction',
                '/manager/operations/v1.5/RemoteStopTransaction',
                '/manager/operations/v1.2/RemoteStopTransaction',
            ];

            foreach ($paths as $path) {
                $url = $baseUrl . $path;
                $formResp = Http::withOptions(['cookies' => $jar])->get($url);
                if (!$formResp->ok()) {
                    continue;
                }

                $formCsrf = $this->extractCsrfToken($formResp->body()) ?: $csrf;

                $postResp = Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])
                    ->asForm()
                    ->post($url, [
                        'chargePointSelectList' => $chargePointSelectValue,
                        'transactionId' => $txId,
                        '_csrf' => $formCsrf,
                    ]);

                if (in_array($postResp->status(), [302, 303], true)) {
                    return ['ok' => true, 'detail' => $path];
                }
            }

            return ['ok' => false, 'detail' => 'RemoteStop failed on all operation paths'];
        } catch (\Throwable $e) {
            Log::error('RemoteStop exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'detail' => $e->getMessage()];
        }
    }

    private function loginAndResolveChargePoint(string $chargeBoxId): array
    {
        $baseUrl = rtrim(env('STEVE_MANAGER_URL', 'http://127.0.0.1:8081'), '/');
        $user = env('STEVE_MANAGER_USER', 'mgr_api');
        $pass = env('STEVE_MANAGER_PASS', 'Mgr#742913');

        $cb = DB::connection('steve')->table('charge_box')->where('charge_box_id', $chargeBoxId)->first();
        if (!$cb) {
            throw new \RuntimeException("ChargeBox {$chargeBoxId} no encontrado en SteVe");
        }

        $ocppProtocol = $cb->ocpp_protocol ?? 'ocpp1.6J';
        $endpointAddress = $cb->endpoint_address ?? '-';
        $ocppToken = str_contains(strtolower($ocppProtocol), '16') ? 'V_16_JSON'
            : (str_contains(strtolower($ocppProtocol), '15') ? 'V_15_JSON' : 'V_12_JSON');
        $chargePointSelectValue = $ocppToken . ';' . $chargeBoxId . ';' . ($endpointAddress ?: '-');

        $jar = new CookieJar();
        $signinUrl = $baseUrl . '/manager/signin';
        $signinResp = Http::withOptions(['cookies' => $jar])->get($signinUrl);
        if (!$signinResp->ok()) {
            throw new \RuntimeException('No se pudo abrir sign-in de SteVe manager');
        }

        $csrf = $this->extractCsrfToken($signinResp->body());
        if (!$csrf) {
            throw new \RuntimeException('No se pudo extraer CSRF token en sign-in');
        }

        $loginResp = Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])
            ->asForm()
            ->post($signinUrl, [
                'username' => $user,
                'password' => $pass,
                '_csrf' => $csrf,
            ]);

        if (!in_array($loginResp->status(), [302, 303], true)) {
            throw new \RuntimeException('Login de SteVe manager falló');
        }

        return [$jar, $csrf, $baseUrl, $chargePointSelectValue];
    }

    private function extractCsrfToken(string $html): ?string
    {
        if (preg_match('/name="_csrf" value="([^"]+)"/', $html, $m)) {
            return $m[1];
        }

        return null;
    }
}
