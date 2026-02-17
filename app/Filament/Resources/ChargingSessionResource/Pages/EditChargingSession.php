<?php

namespace App\Filament\Resources\ChargingSessionResource\Pages;

use App\Filament\Resources\ChargingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChargingSession extends EditRecord
{
    protected static string $resource = ChargingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
