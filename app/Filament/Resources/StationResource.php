<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StationResource\Pages;
use App\Models\Station;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StationResource extends Resource
{
    protected static ?string $model = Station::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = 'Infrastructure';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('OCPP Identity')
                    ->description('These settings link the station to the Steve Server.')
                    ->schema([
                        Forms\Components\TextInput::make('charge_box_id')
                            ->label('Charge Box ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Must match the ChargePointIdentity in the physical charger.'),
                        
                        Forms\Components\Select::make('location_id')
                            ->relationship('location', 'name')
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('address')->required(),
                            ]),

                        Forms\Components\Select::make('tariff_id')
                            ->relationship('tariff', 'name')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Hardware Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('vendor'),
                        Forms\Components\TextInput::make('model'),
                        Forms\Components\TextInput::make('serial_number'),
                    ])->columns(2),

                Forms\Components\Toggle::make('is_active')
                    ->label('Enabled')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('charge_box_id')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('name')->label('Station Name'),
                Tables\Columns\TextColumn::make('location.name')->sortable(),
                Tables\Columns\TextColumn::make('tariff.name')->sortable(),
                Tables\Columns\TextColumn::make('last_heartbeat')->dateTime()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Connectors Relation Manager will go here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStations::route('/'),
            'create' => Pages\CreateStation::route('/create'),
            'edit' => Pages\EditStation::route('/{record}/edit'),
        ];
    }
}
