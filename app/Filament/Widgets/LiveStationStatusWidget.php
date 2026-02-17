<?php

namespace App\Filament\Widgets;

use App\Models\Steve\Station;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LiveStationStatusWidget extends BaseWidget
{
    protected static ?string $heading = 'Live Station Status';

    // Refresh every 3s
    protected static ?string $pollingInterval = '3s';

    // Take up full width
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Station::query()->with(['connectors.status'])
            )
            ->poll('3s') // Enforce polling on the table component itself
            ->columns([
                Tables\Columns\TextColumn::make('charge_box_id')
                    ->label('Station ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Location / Desc')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('station_status')
                    ->label('Station Status')
                    ->state(function ($record) {
                        $stationStatus = $record->connectors->where('connector_id', 0)->first();
                        // Also fetch station status fresh
                        $statusText = 'Available'; // Default to Available if Connector 0 is missing
                        if ($stationStatus) {
                            $stObj = \Illuminate\Support\Facades\DB::connection('steve')->table('connector_status')
                                ->where('connector_pk', $stationStatus->connector_pk)
                                ->orderBy('status_timestamp', 'desc')
                                ->first();
                            $statusText = $stObj->status ?? 'Unknown';
                        } else {
                            // If no Connector 0, infer from Connector 1? Or just say Available
                            // Many CPs don't report Connector 0 status explicitly
                            $statusText = 'Available';
                        }
                        return $statusText;
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Available' => 'success',
                        'Charging' => 'warning',
                        'Faulted', 'Unavailable' => 'danger',
                        'Preparing', 'Finishing' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('connectors')
                    ->label('Connectors (Guns)')
                    ->formatStateUsing(function ($record) {
                        $html = '<div class="flex gap-2">';

                        // Get Station Status (Connector 0)
                        $stationStatusObj = $record->connectors->where('connector_id', 0)->first();
                        $stationStatus = $stationStatusObj ? ($stationStatusObj->status->status ?? 'Available') : 'Available';
                        $isStationDown = in_array($stationStatus, ['Unavailable', 'Faulted']);

                        // Sort by connector_id and filter out 0 (Station Status)
                        $connectors = $record->connectors
                            ->where('connector_id', '>', 0)
                            ->sortBy('connector_id');

                        if ($connectors->isEmpty()) {
                            return '<span class="text-gray-400 text-xs italic">No connectors</span>';
                        }

                        foreach ($connectors as $connector) {
                            // Fetch latest status dynamically if relationship isn't eager loading correctly or is stale
                            // The eager loaded 'status' might be old or weird relationship mapping
                            // Let's get it fresh from DB for reliability
                            
                            $latestStatusObj = \Illuminate\Support\Facades\DB::connection('steve')->table('connector_status')
                                ->where('connector_pk', $connector->connector_pk)
                                ->orderBy('status_timestamp', 'desc')
                                ->first();

                            $realStatus = $latestStatusObj ? $latestStatusObj->status : 'Unknown';
                            
                            // If station is down (heartbeat old?), maybe force unavailable?
                            // But let's trust the connector status for now.
                            $status = $realStatus;

                            $icon = match ($status) {
                                'Available' => '✅',
                                'Charging' => '⚡',
                                'Faulted', 'Unavailable' => '❌',
                                'Preparing' => '⏳',
                                'Finishing' => '🏁',
                                default => '❓',
                            };

                            // Determine color class based on status for visual styling
                            $bgClass = match ($status) {
                                'Available' => 'bg-green-100 text-green-800 border-green-200',
                                'Charging' => 'bg-yellow-100 text-yellow-800 border-yellow-200 animate-pulse',
                                'Faulted', 'Unavailable' => 'bg-red-100 text-red-800 border-red-200',
                                'Preparing', 'Finishing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                default => 'bg-gray-100 text-gray-800 border-gray-200',
                            };

                            // Extra Info for Charging Status (Power / Energy)
                            $extraInfo = '';
                            if ($status === 'Charging') {
                                // Fetch active transaction info from Steve DB for this connector
                                // The connector object here IS from Steve DB (App\Models\Steve\Connector)
                                // So we have connector_pk directly.
                                
                                $connectorPk = $connector->connector_pk;

                                if ($connectorPk) {
                                    // Fetch active transaction info from Steve DB for this connector
                                    $tx = \Illuminate\Support\Facades\DB::connection('steve')->table('transaction')
                                        ->where('connector_pk', $connectorPk)
                                        ->whereNull('stop_timestamp')
                                        ->orderBy('start_timestamp', 'desc')
                                        ->first();

                                    if ($tx) {
                                        // Fetch latest Power (W)
                                        $lastPower = \Illuminate\Support\Facades\DB::connection('steve')->table('connector_meter_value')
                                            ->where('transaction_pk', $tx->transaction_pk)
                                            ->where('measurand', 'Power.Active.Import')
                                            ->orderBy('value_timestamp', 'desc')
                                            ->first();
                                        
                                        if ($lastPower) {
                                            // W to kW
                                            $kw = number_format($lastPower->value / 1000, 1);
                                            $extraInfo = "<span class='block text-[10px] mt-1 font-bold'>⚡ {$kw} kW</span>";
                                        } else {
                                             $extraInfo = "<span class='block text-[10px] mt-1'>...</span>";
                                        }
                                    }
                                }
                            }

                            // Add tooltip note if overrided
                            $title = $isStationDown && $realStatus !== 'Unavailable'
                                ? "Station is $stationStatus (Connector was $realStatus)"
                                : "Connector {$connector->connector_id}: $status";

                            $html .= sprintf(
                                '<div class="px-2 py-1 rounded text-xs font-bold border %s text-center min-w-[60px]" title="%s">
                                    <div>%s %s</div>
                                    <div>%s</div>
                                    %s
                                 </div>',
                                $bgClass,
                                $title,
                                $connector->connector_id,
                                $icon,
                                $status,
                                $extraInfo
                            );
                        }
                        $html .= '</div>';
                        return $html;
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('last_heartbeat_timestamp')
                    ->label('Last Seen')
                    ->since()
                    ->sortable(),
            ]);
    }
}
