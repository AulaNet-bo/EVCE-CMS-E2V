<?php

namespace App\Filament\Resources\TariffResource\Pages;

use App\Filament\Resources\TariffResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTariff extends EditRecord
{
    protected static string $resource = TariffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => !($this->record->hasBeenUsed() || $this->record->isExpired())),
        ];
    }

    protected function beforeFill(): void
    {
        if ($this->record->isExpired()) {
            Notification::make()
                ->warning()
                ->title('Tariff is expired')
                ->body('Expired tariffs are read-only and cannot be modified.')
                ->send();
        } elseif ($this->record->hasBeenUsed()) {
            Notification::make()
                ->warning()
                ->title('Tariff has historical usage')
                ->body('Only "valid until" can be changed for used tariffs.')
                ->send();
        }
    }
}
