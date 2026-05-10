<?php

namespace App\Filament\Resources\LibelulaApiLogResource\Pages;

use App\Filament\Resources\LibelulaApiLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLibelulaApiLogs extends ListRecords
{
    protected static string $resource = LibelulaApiLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action because logs are auto-generated
        ];
    }
}
