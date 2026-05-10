<?php

namespace App\Console\Commands;

use App\Models\Connector;
use App\Models\Station;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\SteveDataSource;

class SyncSteveStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'steve:sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs connector status from Steve Database to CMS';

    /**
     * Execute the console command.
     */
    public function handle(SteveDataSource $source, \App\Services\SteveService $steveService)
    {
        $this->info("Starting synchronization with Steve OCPP Server ({$source->source()})...");

        try {
            $steveConnectors = collect($source->getConnectorsWithStatus());

            $this->info("Found " . $steveConnectors->count() . " connectors in Steve source.");

            foreach ($steveConnectors as $sConnector) {
                // Normalize status with heartbeat check
                $status = $steveService->normalizeStatusWithHeartbeat(
                    $sConnector->status ?? 'Unknown',
                    $sConnector->last_heartbeat_timestamp
                );

                // Find or Create Station in CMS
                $station = Station::updateOrCreate(
                    ['charge_box_id' => $sConnector->charge_box_id],
                    [
                        'name' => 'Station ' . $sConnector->charge_box_id,
                        'is_active' => true,
                        'last_heartbeat' => $sConnector->last_heartbeat_timestamp
                    ]
                );

                // Automatic Tariff Assignment (Only if not set)
                if (!$station->tariff_id) {
                    $now = now();
                    $activeTariff = \App\Models\Tariff::where(function ($query) use ($now) {
                        $query->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
                    })
                        ->where(function ($query) use ($now) {
                            $query->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($activeTariff) {
                        $station->update(['tariff_id' => $activeTariff->id]);
                    }
                }

                // Sync Connector Status
                $connector = Connector::updateOrCreate(
                    [
                        'station_id' => $station->id,
                        'connector_id' => $sConnector->connector_id,
                    ],
                    [
                        'status' => $status,
                        'connector_pk' => $sConnector->connector_pk
                    ]
                );

                $this->line("Synced: {$station->charge_box_id} - Conn {$connector->connector_id} -> {$status}");

                // --- Real-time Sync to Firebase ---
                try {
                    \App\Services\FirebaseService::syncStationData($station->charge_box_id, [
                        'status' => $status,
                        'connectors' => [
                            (string)$connector->connector_id => [
                                'status' => $status
                            ]
                        ]
                    ]);
                } catch (\Throwable $e) {
                    // Silently fail or log for Firebase
                }
            }

            $this->info("Synchronization complete!");

        } catch (\Exception $e) {
            $this->error("Sync Failed: " . $e->getMessage());
            Log::error("Steve Sync Failed", ['error' => $e]);
        }
    }
}
