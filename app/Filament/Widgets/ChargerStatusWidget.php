<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ChargerStatusWidget extends BaseWidget
{
    // Refresh stats every 3s
    protected static ?string $pollingInterval = '3s';

    protected function getStats(): array
    {
        // Fetch all stations with their connectors and latest status
        // We do this in PHP to easily apply the "Station Status overrides Connector Status" logic
        $stations = \App\Models\Steve\Station::with([
            'connectors' => function ($query) {
                $query->orderBy('connector_id');
            },
            'connectors.status'
        ])->get();

        $stats = [
            'Available' => 0,
            'Charging' => 0,
            'Faulted' => 0,
        ];

        foreach ($stations as $station) {
            // Find Station Status (Connector 0)
            $stationConnector = $station->connectors->firstWhere('connector_id', 0);
            
            // Fix: Don't assume station is down if Connector 0 is missing or Unknown. Assume Available.
            $stationStatus = $stationConnector?->status?->status ?? 'Available'; 
            $isStationDown = in_array($stationStatus, ['Unavailable', 'Faulted']);

            // Iterate through physical connectors (ID > 0)
            $guns = $station->connectors->where('connector_id', '>', 0);

            foreach ($guns as $gun) {
                // Determine effective status
                // We MUST re-fetch latest status here too, relying on eager load is failing us
                $statusObj = DB::connection('steve')->table('connector_status')
                    ->where('connector_pk', $gun->connector_pk)
                    ->orderBy('status_timestamp', 'desc')
                    ->first();
                
                $realStatus = $statusObj ? $statusObj->status : 'Unknown';
                
                $effectiveStatus = $isStationDown ? 'Faulted' : $realStatus; // Map station down to Faulted/Other bucket

                // Map to our 3 main widget buckets
                if ($effectiveStatus === 'Available') {
                    $stats['Available']++;
                } elseif ($effectiveStatus === 'Charging') {
                    $stats['Charging']++;
                } else {
                    // Everything else (Faulted, Unavailable, Preparing, Finishing, Unknown) goes to Faulted/Other
                    // You might want to map Preparing/Finishing to Charging or Available depending on preference,
                    // but usually they are "Active/Busy" or "Not Available".
                    // Let's verify standard mapping:
                    if (in_array($effectiveStatus, ['Preparing', 'Finishing', 'SuspendedEV', 'SuspendedEVSE'])) {
                        $stats['Charging']++; // Count active sessions as Charging/Busy
                    } else {
                        $stats['Faulted']++;
                    }
                }
            }
        }

        return [
            Stat::make('Available', $stats['Available'])
                ->description('Ready for sessions')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Charging', $stats['Charging'])
                ->description('Active sessions')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning'),

            Stat::make(
                'Faulted / Other',
                $stats['Faulted']
            )
                ->description('Non-operative or busy')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
        ];
    }
}
