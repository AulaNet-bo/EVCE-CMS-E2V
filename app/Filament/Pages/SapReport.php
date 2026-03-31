<?php

namespace App\Filament\Pages;

use App\Services\SapExportService;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class SapReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Reporte SAP';
    protected static ?string $title = 'Reporte de Integración SAP';
    protected static ?string $navigationGroup = null;

    protected static string $view = 'filament.pages.sap-report';
 
    public static function canAccess(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    public ?string $reportType = 'payments';
    public array $data = [];

    public function mount()
    {
        $this->form->fill([
            'reportType' => 'payments',
        ]);
        $this->loadReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('reportType')
                    ->label('Tipo de Reporte')
                    ->options([
                        'customers' => 'Clientes',
                        'payments' => 'Pagos (Wallet)',
                        'sessions' => 'Sesiones de Carga',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->reportType = $state;
                        $this->loadReport();
                    }),
            ])
            ->columns(2);
    }

    public function loadReport()
    {
        $service = app(SapExportService::class);

        switch ($this->reportType) {
            case 'customers':
                $this->data = $service->getCustomers()->toArray();
                break;
            case 'payments':
                $this->data = $service->getPayments()->toArray();
                break;
            case 'sessions':
                $this->data = $service->getSessions()->toArray();
                break;
            default:
                $this->data = [];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Actualizar')
                ->color('gray')
                ->icon('heroicon-m-arrow-path')
                ->action(fn () => $this->loadReport()),
        ];
    }
}
