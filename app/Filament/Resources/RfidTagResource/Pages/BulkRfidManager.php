<?php

namespace App\Filament\Resources\RfidTagResource\Pages;

use App\Filament\Resources\RfidTagResource;
use App\Models\RfidTag;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Company;
use App\Models\WalletTransaction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BulkRfidManager extends Page
{
    protected static string $resource = RfidTagResource::class;

    protected static string $view = 'filament.resources.rfid-tag-resource.pages.bulk-rfid-manager';

    protected static ?string $title = 'Bulk RFID Manager';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración del Lote')
                    ->description('Ingrese los códigos RFID y configure el destino (Empresa/Usuario).')
                    ->schema([
                        Textarea::make('tag_codes')
                            ->label('Códigos RFID (Uno por línea)')
                            ->placeholder("55778899\nAABBCCDD")
                            ->required()
                            ->rows(8),

                        TextInput::make('credit_amount')
                            ->label('Saldo Inicial (BOB)')
                            ->numeric()
                            ->default(0)
                            ->prefix('BOB'),

                        Select::make('company_id')
                            ->label('Empresa Destino')
                            ->options(Company::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText('Si no se selecciona, se usará la empresa maestra.')
                            ->live(),

                        Select::make('assignment_type')
                            ->label('Tipo de Asignación')
                            ->options([
                                'individual' => 'Un usuario nuevo por cada tarjeta',
                                'corporate' => 'Un solo usuario corporativo para todo el lote',
                                'existing' => 'Vincular a un usuario existente',
                            ])
                            ->default('individual')
                            ->required()
                            ->live(),

                        Section::make('Detalles del Usuario Corporativo / Empresa')
                            ->schema([
                                TextInput::make('new_user_name')
                                    ->label('Nombre del Usuario/Flota')
                                    ->required()
                                    ->placeholder('Ej: Flota Trans-Vía'),
                                TextInput::make('new_user_email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->required(),
                                TextInput::make('billing_razon_social')
                                    ->label('Razón Social'),
                                TextInput::make('billing_document')
                                    ->label('NIT / CI'),
                            ])
                            ->visible(fn(callable $get) => $get('assignment_type') === 'corporate')
                            ->columns(2),

                        Select::make('user_id')
                            ->label('Usuario Existente')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn(callable $get) => $get('assignment_type') === 'existing')
                            ->required(fn(callable $get) => $get('assignment_type') === 'existing'),
                    ])
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $inputData = $this->form->getState();
        $codes = array_filter(array_map('trim', explode("\n", $inputData['tag_codes'])));
        $credit = $inputData['credit_amount'];
        $assignmentType = $inputData['assignment_type'];
        $companyId = $inputData['company_id'];
        $selectedUserId = $inputData['user_id'] ?? null;

        if (empty($codes)) {
            Notification::make()->title('No se proporcionaron códigos')->danger()->send();
            return;
        }

        $createdCount = 0;
        $errorCount = 0;

        DB::beginTransaction();
        try {
            $sharedUserId = null;

            // Scenario: Single corporate user for the whole batch
            if ($assignmentType === 'corporate') {
                $sharedUser = User::create([
                    'name' => $inputData['new_user_name'],
                    'email' => $inputData['new_user_email'],
                    'password' => Hash::make(Str::random(12)),
                    'billing_razon_social' => $inputData['billing_razon_social'],
                    'billing_document' => $inputData['billing_document'],
                    'company_id' => $companyId,
                    'is_admin' => false,
                ]);
                $sharedUser->assignRole('client');
                $sharedUserId = $sharedUser->id;
            }

            foreach ($codes as $code) {
                if (RfidTag::where('tag_code', $code)->exists()) {
                    $errorCount++;
                    continue;
                }

                $userId = $selectedUserId;

                if ($assignmentType === 'individual') {
                    // One new user per tag
                    $user = User::create([
                        'name' => "Usuario RFID $code",
                        'email' => "rfid_$code@evce.temp",
                        'password' => Hash::make(Str::random(12)),
                        'company_id' => $companyId,
                        'is_admin' => false,
                    ]);
                    $user->assignRole('client');
                    $userId = $user->id;
                } elseif ($assignmentType === 'corporate') {
                    $userId = $sharedUserId;
                }

                // Create RFID Tag
                RfidTag::create([
                    'tag_code' => $code,
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'name' => "Tarjeta $code",
                    'is_active' => true,
                ]);

                // Initial Credit
                if ($credit > 0 && $userId) {
                    $wallet = Wallet::firstOrCreate(
                        ['user_id' => $userId],
                        ['balance' => 0, 'currency' => 'BOB']
                    );

                    $wallet->increment('balance', $credit);

                    WalletTransaction::create([
                        'user_id' => $userId,
                        'wallet_id' => $wallet->id,
                        'type' => 'RECHARGE',
                        'amount' => $credit,
                        'balance_after' => $wallet->balance,
                        'currency' => 'BOB',
                        'status' => 'COMPLETED',
                        'description' => 'Carga inicial por lote',
                    ]);
                }

                $createdCount++;
            }

            DB::commit();

            Notification::make()
                ->title('Proceso completado')
                ->body("Se crearon $createdCount tarjetas. $errorCount omitidas por ya existir.")
                ->success()
                ->send();

            $this->form->fill();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title('Error en el proceso')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
