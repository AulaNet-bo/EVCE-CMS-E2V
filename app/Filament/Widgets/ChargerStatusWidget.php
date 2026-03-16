<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ChargerStatusWidget extends BaseWidget
{
    // Refresh stats every 3s
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $stats = [
            'Available' => 0,
            'Charging' => 0,
            'Faulted' => 0,
        ];

        $dataSource = app(\App\Services\SteveDataSource::class);
        $allConnectors = $dataSource->getConnectorsWithStatus();

        // Group by charge_box_id
        $stations = [];
        foreach ($allConnectors as $c) {
            $cbid = $c->charge_box_id;
            if (!isset($stations[$cbid])) {
                $stations[$cbid] = [];
            }
            $stations[$cbid][] = $c;
        }

        foreach ($stations as $cbid => $connectors) {
            // Find Station Status (Connector 0)
            $stationConnector = collect($connectors)->firstWhere('connector_id', 0);
            $stationStatus = $stationConnector ? $stationConnector->status : 'Available';
            $isStationDown = in_array($stationStatus, ['Unavailable', 'Faulted']);

            // Iterate through physical connectors (ID > 0)
            $guns = collect($connectors)->where('connector_id', '>', 0);

            foreach ($guns as $gun) {
                $realStatus = $gun->status ?? 'Unknown';
                $effectiveStatus = $isStationDown ? 'Faulted' : $realStatus; // Map station down to Faulted

                // Map to our 3 main widget buckets
                if ($effectiveStatus === 'Available') {
                    $stats['Available']++;
                } elseif (in_array($effectiveStatus, ['Charging', 'Preparing', 'Finishing', 'SuspendedEV', 'SuspendedEVSE'])) {
                    $stats['Charging']++; // Count active sessions as Charging/Busy
                } else {
                    $stats['Faulted']++;
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
