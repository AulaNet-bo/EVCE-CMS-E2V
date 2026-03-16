<?php

namespace App\Events;

use App\Models\Connector;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectorStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $station_id;
    public $connector_id;
    public $status;

    public function __construct($station_id, $connector_id, $status)
    {
        $this->station_id = $station_id;
        $this->connector_id = $connector_id;
        $this->status = $status;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('stations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'connector.status.updated';
    }
}
