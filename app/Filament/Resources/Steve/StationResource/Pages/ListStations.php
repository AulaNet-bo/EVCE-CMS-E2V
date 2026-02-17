<?php

namespace App\Filament\Resources\Steve\StationResource\Pages;

use App\Filament\Resources\Steve\StationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStations extends ListRecords
{
    protected static string $resource = StationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
