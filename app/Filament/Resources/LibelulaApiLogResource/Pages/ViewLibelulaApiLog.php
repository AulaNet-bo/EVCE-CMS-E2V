<?php

namespace App\Filament\Resources\LibelulaApiLogResource\Pages;

use App\Filament\Resources\LibelulaApiLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLibelulaApiLog extends ViewRecord
{
    protected static string $resource = LibelulaApiLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
