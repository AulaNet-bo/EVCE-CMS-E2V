<?php

namespace App\Console\Commands;

use App\Events\ConnectorStatusUpdated;
use App\Models\Connector;
use App\Services\SteveDataSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class RedisSubscribeSteve extends Command
{
    protected $signature = 'steve:listen';
    protected $description = 'Listen to Steve status changes via Redis Pub/Sub';

    public function handle()
    {
        $prefix = config('steve.redis_prefix', 'steve');
        $channel = "{$prefix}:connector_status:changed";

        $this->info("Listening to Redis channel: {$channel}");

        Redis::subscribe([$channel], function ($message) {
            $this->info("Message received: {$message}");

            $data = json_decode($message, true);
            if (!$data || !isset($data['id'])) {
                return;
            }

            $connectorPk = (int) $data['id'];

            // Find the connector in our local DB to send station context
            $connector = Connector::where('connector_pk', $connectorPk)->first();

            if (!$connector) {
                // If not found in local DB, maybe we need a fresh status check
                $this->warn("Connector PK {$connectorPk} not found in local database.");
                return;
            }

            // Fetch the latest status from Redis to be sure
            $dataSource = app(SteveDataSource::class);
            $newStatus = $dataSource->getLatestConnectorStatus($connectorPk);

            if ($newStatus) {
                $this->info("Broadcasting status update: Station {$connector->station_id}, Connector {$connector->connector_id} -> {$newStatus}");

                // Broadcast the event
                broadcast(new ConnectorStatusUpdated(
                    $connector->station_id,
                    $connector->connector_id,
                    $newStatus
                ));

                // Also update local DB status so they are in sync
                $connector->update(['status' => $newStatus]);
            }
        });
    }
}
