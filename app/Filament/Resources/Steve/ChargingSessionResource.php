<?php

namespace App\Filament\Resources\Steve;

use App\Filament\Resources\Steve\ChargingSessionResource\Pages;
use App\Filament\Resources\Steve\ChargingSessionResource\RelationManagers;
use App\Models\Steve\ChargingSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChargingSessionResource extends Resource
{
    protected static ?string $model = ChargingSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationGroup = 'System / Debug';
    protected static ?string $navigationLabel = 'Raw Logs (Steve)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('transaction_pk')
                    ->label('Transaction ID')
                    ->disabled(),
                Forms\Components\TextInput::make('idTag')
                    ->label('RFID Tag')
                    ->disabled(),
                Forms\Components\TextInput::make('connector.charge_box_id')
                    ->label('Station ID')
                    ->disabled(),
                Forms\Components\TextInput::make('connector.connector_id')
                    ->label('Connector ID')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('startTimestamp')
                    ->label('Start Time'),
                Forms\Components\DateTimePicker::make('stopTimestamp')
                    ->label('Stop Time'),
                Forms\Components\TextInput::make('startValue')
                    ->label('Meter Start (Wh)')
                    ->numeric(),
                Forms\Components\TextInput::make('stopValue')
                    ->label('Meter Stop (Wh)')
                    ->numeric(),
                Forms\Components\TextInput::make('stopValue') // Intentionally using accessor logic in table, raw here
                    ->label('Stop Reason')
                    ->formatStateUsing(fn($record) => $record->stop_reason ?? '-'), // stop_reason might not exist in table, check schema
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_pk')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('connector.charge_box_id')
                    ->label('Station')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    // Show friendly name from CMS if available
                    ->description(function ($state) {
                        $station = \App\Models\Station::where('charge_box_id', $state)->first();
                        return $station ? $station->name : null;
                    }),

                Tables\Columns\TextColumn::make('connector.connector_id')
                    ->label('Gun #')
                    ->alignment('center')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('id_tag') // Changed from idTag to id_tag (DB column name)
                    ->label('Client / RFID')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return '-';
                        // Try to find the user in CMS database associated with this tag
                        $tag = \App\Models\RfidTag::where('tag_code', $state)->with('user')->first();
                        if ($tag && $tag->user) {
                            return $tag->user->name;
                        }
                        return "Unknown User";
                    })
                    ->description(fn ($state) => $state), // Show Tag Code in description

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Active' => 'success', // Active is good!
                        'Finished' => 'gray',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('start_timestamp')
                    ->label('Started')
                    ->dateTime('d M H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stop_timestamp')
                    ->label('Stopped')
                    ->dateTime('d M H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration'),

                Tables\Columns\TextColumn::make('energy_consumed') // Uses the accessor
                    ->label('Energy (kWh)')
                    ->numeric(2)
                    ->badge()
                    ->color('info'),
            ])
            ->defaultSort('transaction_pk', 'desc')
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn($query) => $query->whereNull('stop_timestamp'))
                    ->label('Active Sessions'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MeterValuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChargingSessions::route('/'),
            'view' => Pages\ViewChargingSession::route('/{record}'),
        ];
    }
}
