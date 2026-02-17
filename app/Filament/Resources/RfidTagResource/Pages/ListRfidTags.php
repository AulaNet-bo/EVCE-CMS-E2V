<?php

namespace App\Filament\Resources\RfidTagResource\Pages;

use App\Filament\Resources\RfidTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRfidTags extends ListRecords
{
    protected static string $resource = RfidTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
