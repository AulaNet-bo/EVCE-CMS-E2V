<?php

namespace App\Filament\Resources\Steve\ChargingSessionResource\Pages;

use App\Filament\Resources\Steve\ChargingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChargingSessions extends ListRecords
{
    protected static string $resource = ChargingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
