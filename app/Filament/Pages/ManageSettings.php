<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Actions\Action;

class ManageSettings extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configuración General';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 100;
    protected static ?string $title = 'Configuración de la Plataforma';

    protected static string $view = 'filament.pages.manage-settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SystemSetting::get()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identidad de la Plataforma')
                    ->description('Configure los datos básicos del sistema.')
                    ->schema([
                        TextInput::make('platform_name')
                            ->label('Nombre de la Plataforma')
                            ->required(),
                        FileUpload::make('logo_path')
                            ->label('Logo de la Plataforma')
                            ->image()
                            ->directory('platform')
                            ->visibility('public'),
                    ]),

                Section::make('Aviso Legal (Disclaimer)')
                    ->description('Configuración del texto legal que verán los usuarios.')
                    ->schema([
                        Textarea::make('disclaimer_text')
                            ->label('Texto del Disclaimer')
                            ->rows(5),
                        Toggle::make('is_disclaimer_visible')
                            ->label('¿Mostrar Disclaimer en App?')
                            ->helperText('Habilita o deshabilita la visualización del disclaimer en los enlaces públicos.'),
                        Actions::make([
                            FormAction::make('view_disclaimer')
                                ->label('Ver Disclaimer Público')
                                ->icon('heroicon-o-eye')
                                ->url(url('/disclaimer'), true)
                                ->color('info')
                                ->button(),
                        ]),
                    ]),

                Section::make('Estética de la Aplicación Móvil')
                    ->description('Personalice los colores y la tipografía de la app.')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Color Primario'),
                        ColorPicker::make('secondary_color')
                            ->label('Color Secundario'),
                        ColorPicker::make('button_color')
                            ->label('Color de Botones'),
                        ColorPicker::make('text_color')
                            ->label('Color de Texto Principal'),
                        Select::make('font_family')
                            ->label('Tipografía (Google Fonts)')
                            ->options([
                                'Inter' => 'Inter',
                                'Roboto' => 'Roboto',
                                'Open Sans' => 'Open Sans',
                                'Montserrat' => 'Montserrat',
                                'Poppins' => 'Poppins',
                                'Outfit' => 'Outfit',
                            ])
                            ->searchable(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $settings = SystemSetting::get();
        $settings->update($this->form->getState());

        Notification::make()
            ->title('Configuración guardada correctamente')
            ->success()
            ->send();
    }
}
