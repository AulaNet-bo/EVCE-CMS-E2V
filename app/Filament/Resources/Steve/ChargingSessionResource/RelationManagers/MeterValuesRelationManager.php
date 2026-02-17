<?php

namespace App\Filament\Resources\Steve\ChargingSessionResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeterValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'meterValues';

    protected static ?string $title = 'Meter Logs (Live & Periodic)';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('value_timestamp')
                    ->label('Timestamp')
                    ->dateTime('H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('measurand')
                    ->label('Metric')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Energy.Active.Import.Register' => 'success',
                        'Power.Active.Import' => 'danger',
                        'Voltage' => 'warning',
                        'Current.Import' => 'info',
                        'SoC' => 'primary',
                        default => 'gray',
                    })
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->numeric()
                    ->weight('bold')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('reading_context')
                    ->label('Context')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('phase')
                    ->label('Phase')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('value_timestamp', 'desc')
            ->headerActions([
                // No create/edit for logs
            ])
            ->actions([
                // No actions
            ])
            ->bulkActions([
                // No bulk actions
            ]);
    }
}
