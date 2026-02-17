<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function index(Request $request)
    {
        // Return active stations with their connectors
        $stations = Station::with('connectors')
            ->where('is_active', true)
            ->get();

        return response()->json($stations);
    }

    public function show(Station $station)
    {
        return response()->json($station->load('connectors', 'location'));
    }
}
