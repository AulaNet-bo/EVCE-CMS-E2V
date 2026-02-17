<?php

namespace App\Filament\Resources\RfidTagResource\Pages;

use App\Filament\Resources\RfidTagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRfidTag extends EditRecord
{
    protected static string $resource = RfidTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
