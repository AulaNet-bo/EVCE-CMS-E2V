<?php

namespace App\Filament\Resources\Steve;

use App\Filament\Resources\Steve\StationResource\Pages;
use App\Filament\Resources\Steve\StationResource\RelationManagers;
use App\Models\Steve\Station;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StationResource extends Resource
{
    protected static ?string $model = Station::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('charge_box_id')
                    ->required()
                    ->maxLength(255)
                    ->label('Charge Point ID'),
                Forms\Components\TextInput::make('endpoint_address')
                    ->maxLength(255)
                    ->label('Endpoint URL'),
                Forms\Components\TextInput::make('firmware_version')
                    ->maxLength(255),
                Forms\Components\TextInput::make('vendor')
                    ->maxLength(255),
                Forms\Components\TextInput::make('model')
                    ->maxLength(255),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('last_heartbeat_timestamp')
                    ->label('Last Heartbeat'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('charge_box_id')
                    ->searchable()
                    ->sortable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('firmware_version')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_heartbeat_timestamp')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Heartbeat'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
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
