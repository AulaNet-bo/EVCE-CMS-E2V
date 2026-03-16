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

    // Refresh every 10s to reduce load
    protected static ?string $pollingInterval = '10s';

    // Take up full width
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Station::query()->with(['connectors.status'])
            )
            ->poll('10s') // Enforce polling on the table component itself
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
                    ->state(function ($record, Tables\Columns\TextColumn $column) {
                        // Batch fetch for the whole request if not done
                        static $statusesBatch = null;
                        $dataSource = app(\App\Services\SteveDataSource::class);

                        if ($statusesBatch === null) {
                            // Extract all connector PKs from the current query result if possible
                            // However, since we are in a row-level closure, we'll try to get them from the table's records
                            $allRecords = $column->getTable()->getRecords();
                            $pks = [];
                            foreach ($allRecords as $row) {
                                foreach ($row->connectors as $conn) {
                                    $pks[] = $conn->connector_pk;
                                }
                            }
                            $statusesBatch = $dataSource->getMultipleConnectorStatuses($pks);
                        }

                        $stationStatus = $record->connectors->where('connector_id', 0)->first();
                        $statusText = 'Available';
                        if ($stationStatus) {
                            $statusText = $statusesBatch[$stationStatus->connector_pk] ?? 'Unknown';
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
                    ->formatStateUsing(function ($record, Tables\Columns\TextColumn $column) {
                        // Reuse the batch from the previous column closure!
                        static $statusesBatch = null;
                        static $activeTxs = null;
                        $dataSource = app(\App\Services\SteveDataSource::class);

                        if ($statusesBatch === null) {
                            $allRecords = $column->getTable()->getRecords();
                            $pks = [];
                            foreach ($allRecords as $row) {
                                foreach ($row->connectors as $conn) {
                                    $pks[] = $conn->connector_pk;
                                }
                            }
                            $statusesBatch = $dataSource->getMultipleConnectorStatuses($pks);
                        }

                        if ($activeTxs === null) {
                            $activeTxs = collect($dataSource->getTransactionsForMonitoring(50));
                        }

                        $html = '<div class="flex gap-2">';

                        // Get Station Status (Connector 0)
                        $stationStatusObj = $record->connectors->where('connector_id', 0)->first();
                        $stationStatus = $stationStatusObj ? ($statusesBatch[$stationStatusObj->connector_pk] ?? 'Available') : 'Available';
                        $isStationDown = in_array($stationStatus, ['Unavailable', 'Faulted']);

                        $connectors = $record->connectors
                            ->where('connector_id', '>', 0)
                            ->sortBy('connector_id');

                        if ($connectors->isEmpty()) {
                            return '<span class="text-gray-400 text-xs italic">No connectors</span>';
                        }

                        foreach ($connectors as $connector) {
                            $realStatus = $statusesBatch[$connector->connector_pk] ?? 'Unknown';
                            $status = $realStatus;

                            $icon = match ($status) {
                                'Available' => '✅',
                                'Charging' => '⚡',
                                'Faulted', 'Unavailable' => '❌',
                                'Preparing' => '⏳',
                                'Finishing' => '🏁',
                                default => '❓',
                            };

                            $bgClass = match ($status) {
                                'Available' => 'bg-green-100 text-green-800 border-green-200',
                                'Charging' => 'bg-yellow-100 text-yellow-800 border-yellow-200 animate-pulse',
                                'Faulted', 'Unavailable' => 'bg-red-100 text-red-800 border-red-200',
                                'Preparing', 'Finishing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                default => 'bg-gray-100 text-gray-800 border-gray-200',
                            };

                            $extraInfo = '';
                            if ($status === 'Charging') {
                                // Find active transaction fast from statically loaded memory collection!
                                $tx = $activeTxs->where('connector_pk', $connector->connector_pk)->whereNull('stop_timestamp')->first();

                                if ($tx) {
                                    $lastPower = $dataSource->getLatestMeterValue($tx->transaction_pk, 'Power.Active.Import');

                                    if ($lastPower) {
                                        $kw = number_format($lastPower->value / 1000, 1);
                                        $extraInfo = "<span class='block text-[10px] mt-1 font-bold'>⚡ {$kw} kW</span>";
                                    } else {
                                        $extraInfo = "<span class='block text-[10px] mt-1'>...</span>";
                                    }
                                }
                            }

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
