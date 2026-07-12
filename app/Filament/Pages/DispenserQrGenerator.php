<?php

namespace App\Filament\Pages;

use App\Models\Connector;
use App\Models\Station;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;

class DispenserQrGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = 'Generador de QR';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'Generador de Códigos QR';

    protected static string $view = 'filament.pages.dispenser-qr-generator';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'staff_admin', 'sales']) ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('station_id')
                    ->label('Filter by Station')
                    ->options(Station::pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->placeholder('All Stations'),
            ])
            ->statePath('data');
    }

    public function getConnectorsProperty()
    {
        $query = Connector::with('station')->where('connector_id', '>', 0);

        if ($stationId = $this->data['station_id'] ?? null) {
            $query->where('station_id', $stationId);
        }

        return $query->get();
    }
}
