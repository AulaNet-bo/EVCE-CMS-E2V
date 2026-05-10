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

                        Section::make('Detalles de la Tarjeta Física')
                            ->schema([
                                Select::make('card_product_id')
                                    ->label('Producto de Venta (Tarjeta)')
                                    ->options(\App\Models\Product::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Producto SIAT que representa el plástico de la tarjeta.'),
                                TextInput::make('card_price')
                                    ->label('Precio de la Tarjeta (BOB)')
                                    ->numeric()
                                    ->default(20)
                                    ->required()
                                    ->live(),
                                TextInput::make('card_discount')
                                    ->label('Descuento de Tarjeta (BOB)')
                                    ->numeric()
                                    ->default(20)
                                    ->required()
                                    ->live()
                                    ->helperText('Si el descuento es igual al precio, el cliente no paga por la tarjeta, pero aparece en la factura.'),
                            ])->columns(3),

                        Section::make('Detalles de Recarga Inicial')
                            ->schema([
                                Select::make('recharge_product_id')
                                    ->label('Producto de Recarga')
                                    ->options(\App\Models\Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Producto SIAT que representa el servicio de recarga de saldo.'),
                                TextInput::make('credit_amount')
                                    ->label('Saldo a Recargar (BOB)')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                            ])->columns(2),

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

                        Section::make('Facturación y Pago')
                            ->schema([
                                Toggle::make('emit_invoice')
                                    ->label('Emitir Factura Oficial (Libélula)')
                                    ->default(fn() => \App\Models\SystemSetting::get()->invoice_on_bulk_creation)
                                    ->live(),
                                
                                Select::make('payment_method')
                                    ->label('Método de Pago')
                                    ->options([
                                        'manual' => 'Efectivo / Manual (Caja)',
                                        'libelula' => 'Pasarela de Pago (QR/Tarjeta)',
                                    ])
                                    ->default('manual')
                                    ->required()
                                    ->visible(fn(callable $get) => $get('emit_invoice')),
                            ])->columns(2),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $inputData = $this->form->getState();
        $codes = array_filter(array_map('trim', explode("\n", $inputData['tag_codes'])));
        $credit = (float) ($inputData['credit_amount'] ?? 0);
        $cardPrice = (float) ($inputData['card_price'] ?? 0);
        $cardDiscount = (float) ($inputData['card_discount'] ?? 0);
        $assignmentType = $inputData['assignment_type'];
        $companyId = $inputData['company_id'] ?? null;
        $selectedUserId = $inputData['user_id'] ?? null;
        $cardProductId = $inputData['card_product_id'];
        $rechargeProductId = $inputData['recharge_product_id'];

        $totalToPay = max(0, $cardPrice - $cardDiscount) + $credit;

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
                // Standardize to 8 characters (remove colons, padding, etc)
                $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
                
                if (strlen($code) > 8) {
                    $code = substr($code, -8);
                }
                
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
                $tag = RfidTag::create([
                    'tag_code' => $code,
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'product_id' => $cardProductId, // Store the physical card product internally
                    'name' => "Tarjeta $code",
                    'balance' => $credit, // Assign initial credit to the tag!
                    'currency' => 'BOB',
                    'is_active' => true,
                ]);

                // Record initial transaction (for history/tracking)
                if ($totalToPay > 0 && $userId) {
                    $wallet = Wallet::firstOrCreate(
                        ['user_id' => $userId],
                        ['balance' => 0, 'currency' => 'BOB']
                    );

                    // If manual, stay PENDING and don't increment balance yet
                    $isManual = ($this->data['payment_method'] ?? 'manual') === 'manual';
                    $shouldInvoice = $this->data['emit_invoice'] ?? false;
                    
                    $lineItems = [];
                    
                    // Line item 1: The physical card
                    if ($cardPrice > 0 || $cardDiscount > 0) {
                        $cardSiatCode = \App\Models\Product::find($cardProductId)?->siat_product_code ?? '1';
                        $lineItems[] = [
                            'concepto' => 'Venta de Tarjeta Física RFID',
                            'cantidad' => 1,
                            'costo_unitario' => $cardPrice,
                            'descuento_unitario' => $cardDiscount,
                            'detalle' => "Tarjeta Plástica $code",
                            'codigo_producto' => $cardSiatCode,
                            'ignora_factura' => false,
                        ];
                    }
                    
                    // Line item 2: The recharge service
                    if ($credit > 0) {
                        $rechargeSiatCode = \App\Models\Product::find($rechargeProductId)?->siat_product_code ?? '1';
                        $lineItems[] = [
                            'concepto' => 'Recarga de Saldo Billetera',
                            'cantidad' => 1,
                            'costo_unitario' => $credit,
                            'descuento_unitario' => 0,
                            'detalle' => "Recarga inicial tarjeta $code",
                            'codigo_producto' => $rechargeSiatCode,
                            'ignora_factura' => false,
                        ];
                    }

                    $tx = WalletTransaction::create([
                        'user_id' => $userId,
                        'wallet_id' => $wallet->id,
                        'type' => 'RECHARGE',
                        'amount' => $totalToPay, // Total payment recorded
                        'balance_after' => $tag->balance,
                        'currency' => 'BOB',
                        'reference_id' => 'BULK-' . $code . '-' . now()->format('YmdHis'),
                        'status' => $isManual ? 'PENDING' : 'PENDING', // Both start pending for Libelula/Manual now
                        'description' => 'Carga inicial y venta de tarjeta RFID',
                        'metadata' => [
                            'should_invoice' => $shouldInvoice,
                            'razon_social' => $this->data['billing_razon_social'] ?? null,
                            'documento' => $this->data['billing_document'] ?? null,
                            'line_items' => $lineItems,
                        ]
                    ]);

                    // If it's Libelula Gateway, create the payment link
                    if (!$isManual) {
                        $libService = app(\App\Services\LibelulaPaymentService::class);
                        
                        $result = $libService->createPayment($wallet, $totalToPay, 'Carga inicial y venta de tarjeta', [
                            'emite_factura' => $shouldInvoice,
                            'internal_usage_tx' => true,
                            'transaction_id' => $tx->id,
                            'line_items' => $lineItems,
                        ], false); // isPaid = false

                        if (!$result['success']) {
                            throw new \Exception("Libélula: " . ($result['detail'] ?? $result['message'] ?? 'Error desconocido'));
                        }
                    }
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
