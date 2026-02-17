<?php

namespace App\Console\Commands;

use App\Models\ChargingSession;
use App\Models\Connector;
use App\Models\RfidTag;
use App\Models\Station;
use App\Models\Steve\ChargingSession as SteveSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSteveSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'steve:sync-sessions {--since= : Only sync sessions with start_timestamp >= this (YYYY-MM-DD HH:MM:SS)} {--limit=200 : Max records to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs charging sessions from Steve DB into CMS charging_sessions table';

    public function handle(): int
    {
        $this->info('Starting Steve session sync...');

        try {
            $limit = (int) $this->option('limit');
            $since = $this->option('since');

            $query = SteveSession::query();

            if ($since) {
                $query->where('start_timestamp', '>=', $since);
            } else {
                // Use max transaction_id in CMS to fetch only newer ones
                $maxTx = ChargingSession::max('transaction_id');
                if ($maxTx) {
                    $query->where('transaction_pk', '>', (int) $maxTx);
                }
            }

            $sessions = $query->orderBy('transaction_pk', 'asc')->limit($limit)->get();

            if ($sessions->isEmpty()) {
                $this->info('No new sessions to sync.');
                return self::SUCCESS;
            }

            $synced = 0;
            foreach ($sessions as $s) {
                // Resolve Station + Connector
                $steveConnector = $s->connector; // App\Models\Steve\Connector
                if (!$steveConnector) {
                    $this->warn("Skipping tx {$s->transaction_pk}: connector not found in Steve.");
                    continue;
                }

                $station = Station::firstOrCreate(
                    ['charge_box_id' => $steveConnector->charge_box_id],
                    ['name' => 'Station ' . $steveConnector->charge_box_id, 'is_active' => true]
                );

                $connector = Connector::updateOrCreate(
                    ['station_id' => $station->id, 'connector_id' => $steveConnector->connector_id],
                    ['status' => 'Unknown', 'connector_pk' => $steveConnector->connector_pk ?? null]
                );

                // Resolve RFID/User
                $rfid = RfidTag::where('tag_code', $s->id_tag)->first();

                // Time + meter values
                $startTime = $s->start_timestamp ?: $s->start_event_timestamp;
                $stopTime = $s->stop_timestamp;
                $meterStart = is_numeric($s->start_value) ? (int) $s->start_value : 0;
                $meterStop = is_numeric($s->stop_value) ? (int) $s->stop_value : 0;
                $energyKwh = ($meterStop > $meterStart) ? round(($meterStop - $meterStart) / 1000, 4) : 0;

                $status = $stopTime ? 'Completed' : 'Active';

                ChargingSession::updateOrCreate(
                    ['transaction_id' => (string) $s->transaction_pk],
                    [
                        'station_id' => $station->id,
                        'connector_id' => $connector->id,
                        'user_id' => $rfid?->user_id,
                        'rfid_tag_id' => $rfid?->id,
                        'tariff_id' => $station->tariff_id,
                        'start_time' => $startTime,
                        'stop_time' => $stopTime,
                        'meter_start' => $meterStart,
                        'meter_stop' => $meterStop,
                        'total_energy_kwh' => $energyKwh,
                        'total_cost' => 0,
                        'currency' => 'USD',
                        'status' => $status,
                        'stop_reason' => $s->stop_reason,
                    ]
                );

                $synced++;
            }

            $this->info("Synced {$synced} sessions.");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('Steve session sync failed', ['error' => $e]);
            return self::FAILURE;
        }
    }
}
