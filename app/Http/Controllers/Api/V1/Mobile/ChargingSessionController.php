<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ChargingSession;
use App\Models\Station;
use Illuminate\Http\Request;

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
        // TODO: Call Steve API to RemoteStartTransaction
        
        return response()->json([
            'message' => 'Charging session start requested',
            'station' => $station->name,
            'status' => 'pending'
        ]);
    }

    public function stop(Request $request, Station $station)
    {
        // TODO: Call Steve API to RemoteStopTransaction
        
        return response()->json([
            'message' => 'Charging session stop requested',
            'station' => $station->name,
            'status' => 'pending'
        ]);
    }
}
