<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\ChargingSession;
use App\Services\SteveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class RemoteControlController extends Controller
{
    public function showLogin()
    {
        $this->ensureDefaultUsersExist();
        if (Session::has('remote_operator_id')) {
            return redirect()->route('remote.index');
        }
        return view('remote-login');
    }

    public function login(Request $request)
    {
        $this->ensureDefaultUsersExist();
        $username = $request->input('username');
        $password = $request->input('password');

        $operator = DB::table('remote_operators')->where('username', $username)->first();

        if ($operator) {
            if (Hash::check($password, $operator->password)) {
                Log::info("RemoteControl: Login successful for user: {$username}");
                Session::put('remote_operator_id', $operator->id);
                Session::put('remote_operator_name', $operator->username);
                return redirect()->route('remote.index');
            } else {
                Log::warning("RemoteControl: Login failed (Wrong Password) for user: {$username}");
            }
        } else {
            Log::warning("RemoteControl: Login failed (User not found): {$username}");
        }

        return back()->with('error', 'Credenciales inválidas');
    }

    public function logout()
    {
        Session::forget(['remote_operator_id', 'remote_operator_name']);
        return redirect()->route('remote.login');
    }

    private function checkAuth()
    {
        if (!Session::has('remote_operator_id')) {
            return false;
        }
        return true;
    }

    public function index(Request $request)
    {
        // Emergency Fallback for URL-based auth
        if ($request->has('u') && $request->has('p')) {
            $username = $request->input('u');
            $password = $request->input('p');
            $operator = DB::table('remote_operators')->where('username', $username)->first();
            if ($operator && Hash::check($password, $operator->password)) {
                Session::put('remote_operator_id', $operator->id);
                Session::put('remote_operator_name', $operator->username);
            }
        }

        if (!$this->checkAuth()) return redirect()->route('remote.login');

        $stations = Station::with(['connectors'])
            ->where('is_active', true)
            ->where('charge_box_id', 'NOT LIKE', 'test%')
            ->get();
            
        $activeSessions = ChargingSession::where('status', 'CHARGING')->get()->keyBy(function($s) {
            return $s->charge_box_id . '-' . $s->connector_id;
        });

        return view('remote-control', compact('stations', 'activeSessions'));
    }

    public function start(Request $request, Station $station)
    {
        if (!$this->checkAuth()) return response()->json(['ok' => false, 'detail' => 'Unauthorized'], 401);

        $connectorId = $request->input('connector_id', 1);
        $idTag = '046E9C92746180'; // Admin/Master Tag

        $steve = app(SteveService::class);
        $result = $steve->remoteStart($station->charge_box_id, (int)$connectorId, $idTag, Session::get('remote_operator_id'));

        // Audit Log
        DB::table('remote_audit_logs')->insert([
            'operator_id' => Session::get('remote_operator_id'),
            'username' => Session::get('remote_operator_name'),
            'action' => 'START',
            'charge_box_id' => $station->charge_box_id,
            'connector_id' => (int)$connectorId,
            'details' => $result['ok'] ? 'Success' : 'Failed: ' . $result['detail'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', $result['ok'] ? 'Comando START enviado con éxito' : 'Error: ' . $result['detail']);
    }

    public function stop(Request $request, Station $station)
    {
        if (!$this->checkAuth()) return response()->json(['ok' => false, 'detail' => 'Unauthorized'], 401);

        $connectorId = $request->input('connector_id', 1);
        
        $session = ChargingSession::where('charge_box_id', $station->charge_box_id)
            ->where('connector_id', $connectorId)
            ->where('status', 'CHARGING')
            ->first();

        if (!$session || !$session->transaction_id) {
            return back()->with('status', 'No se encontró una sesión activa para detener.');
        }

        $steve = app(SteveService::class);
        $result = $steve->remoteStop($station->charge_box_id, (int)$session->transaction_id, Session::get('remote_operator_id'));

        if ($result['ok']) {
            $session->update(['status' => 'COMPLETED', 'finished_at' => now()]);
        }

        // Audit Log
        DB::table('remote_audit_logs')->insert([
            'operator_id' => Session::get('remote_operator_id'),
            'username' => Session::get('remote_operator_name'),
            'action' => 'STOP',
            'charge_box_id' => $station->charge_box_id,
            'connector_id' => (int)$connectorId,
            'details' => $result['ok'] ? 'Success' : 'Failed: ' . $result['detail'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', $result['ok'] ? 'Comando STOP enviado con éxito' : 'Error: ' . $result['detail']);
    }

    private function ensureDefaultUsersExist()
    {
        $users = [
            ['username' => 'jorge', 'password' => 'electro2024'],
            ['username' => 'op1', 'password' => 'ep-9921'],
            ['username' => 'op2', 'password' => 'ep-4432'],
            ['username' => 'op3', 'password' => 'ep-1188'],
            ['username' => 'op4', 'password' => 'ep-7762'],
        ];

        foreach ($users as $u) {
            DB::table('remote_operators')->updateOrInsert(
                ['username' => $u['username']],
                [
                    'password' => Hash::make($u['password']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

}
