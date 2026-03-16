<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        // Return active locations with their stations and connectors
        $locations = Location::with([
            'stations' => function ($query) {
                $query->where('is_active', true);
            },
            'stations.connectors'
        ])
            ->whereHas('stations', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        // Enrich with real-time status from Redis
        $dataSource = app(\App\Services\SteveDataSource::class);
        $connectorPks = $locations->flatMap->stations->flatMap->connectors->pluck('connector_pk')->unique()->filter()->all();
        $statuses = $dataSource->getMultipleConnectorStatuses($connectorPks);

        $locations->each(function ($location) use ($statuses) {
            $location->stations->each(function ($station) use ($statuses) {
                $station->connectors->each(function ($connector) use ($statuses) {
                    $connector->status = $statuses[$connector->connector_pk] ?? 'Available';
                });
            });
        });

        return response()->json($locations);
    }
}
