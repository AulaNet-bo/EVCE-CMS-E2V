<?php

namespace App\Filament\Resources\StationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ConnectorsRelationManager extends RelationManager
{
    protected static string $relationship = 'connectors';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('connector_id')
                    ->label('Connector ID')
                    ->disabled()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Connector Type')
                    ->options([
                        'CCS2' => 'CCS Type 2',
                        'GBT' => 'GB/T (DC)',
                        'CHAdeMO' => 'CHAdeMO',
                        'Type2' => 'Type 2 (AC)',
                        'Type1' => 'Type 1 (J1772)',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('max_power_kw')
                    ->label('Max Power (kW)')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('status')
                    ->label('Current Status')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('connector_id')
            ->columns([
                Tables\Columns\TextColumn::make('connector_id')
                    ->label('ID'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'CCS2' => 'info',
                        'GBT' => 'warning',
                        'CHAdeMO' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('max_power_kw')
                    ->label('Power (kW)')
                    ->suffix(' kW'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Available' => 'success',
                        'Occupied', 'Charging' => 'warning',
                        'Faulted' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
