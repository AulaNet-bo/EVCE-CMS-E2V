<?php

namespace App\Console\Commands;

use App\Models\Connector;
use App\Models\Station;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public function handle()
    {
        $this->info("Starting synchronization with Steve OCPP Server...");

        try {
            // 1. Fetch live connectors from Steve DB
            // We need to JOIN connector, charge_box, and connector_status to get the full picture
            // The status is in 'connector_status' table (latest entry per connector)
            
            $steveConnectors = DB::connection('steve')
                ->table('connector')
                ->join('charge_box', 'connector.charge_box_id', '=', 'charge_box.charge_box_id')
                ->select(
                    'charge_box.charge_box_id', 
                    'connector.connector_id', 
                    'charge_box.last_heartbeat_timestamp',
                    'connector.connector_pk'
                )
                ->get();

            $this->info("Found " . $steveConnectors->count() . " connectors in Steve DB.");

            foreach ($steveConnectors as $sConnector) {
                // Find Latest Status
                $latestStatus = DB::connection('steve')->table('connector_status')
                    ->where('connector_pk', $sConnector->connector_pk)
                    ->orderBy('status_timestamp', 'desc')
                    ->first();
                
                $status = $latestStatus ? $latestStatus->status : 'Unknown';

                // Find or Create Station in CMS
                $station = Station::firstOrCreate(
                    ['charge_box_id' => $sConnector->charge_box_id],
                    [
                        'name' => 'Station ' . $sConnector->charge_box_id,
                        'is_active' => true,
                    ]
                );
                
                // Update Heartbeat
                if ($sConnector->last_heartbeat_timestamp) {
                    $station->last_heartbeat = $sConnector->last_heartbeat_timestamp;
                    $station->save();
                }

                // Sync Connector Status
                $connector = Connector::updateOrCreate(
                    [
                        'station_id' => $station->id,
                        'connector_id' => $sConnector->connector_id,
                    ],
                    [
                        'status' => $status,
                        'connector_pk' => $sConnector->connector_pk // Save PK for easier lookups later if needed
                    ]
                );

                $this->line("Synced: {$station->charge_box_id} - Conn {$connector->connector_id} -> {$status}");
            }

            $this->info("Synchronization complete!");

        } catch (\Exception $e) {
            $this->error("Sync Failed: " . $e->getMessage());
            Log::error("Steve Sync Failed", ['error' => $e]);
        }
    }
}
