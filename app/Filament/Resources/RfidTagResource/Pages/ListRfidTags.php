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
            Actions\Action::make('bulk_manager')
                ->label('Bulk RFID Manager')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(RfidTagResource::getUrl('bulk')),
        ];
    }
}
