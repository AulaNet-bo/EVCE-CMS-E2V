<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function index(Request $request)
    {
        // Return active stations with their connectors and location for the map
        $stations = Station::with(['connectors', 'location'])
            ->where('is_active', true)
            ->get();

        // Enrich with real-time status from Redis
        $dataSource = app(\App\Services\SteveDataSource::class);
        $connectorPks = $stations->flatMap->connectors->pluck('connector_pk')->unique()->filter()->all();
        $statuses = $dataSource->getMultipleConnectorStatuses($connectorPks);

        $stations->each(function ($station) use ($statuses) {
            $station->connectors->each(function ($connector) use ($statuses) {
                $connector->status = $statuses[$connector->connector_pk] ?? 'Available';
            });
        });

        return response()->json($stations);
    }

    public function show(Station $station)
    {
        return response()->json($station->load('connectors', 'location'));
    }

    public function lookup(Request $request)
    {
        $request->validate(['charge_box_id' => 'required|string']);

        $station = Station::with(['connectors', 'location'])
            ->where('identity', $request->charge_box_id)
            ->firstOrFail();

        return response()->json($station);
    }
}
