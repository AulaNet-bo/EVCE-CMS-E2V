<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Finance';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Wallet Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true), // One wallet per user
                        
                        Forms\Components\Select::make('currency')
                            ->options([
                                'BOB' => 'BOB (Boliviano)',
                                'USD' => 'USD (Dólar)',
                            ])
                            ->default('BOB')
                            ->required(),

                        Forms\Components\TextInput::make('balance')
                            ->label('Current Balance')
                            ->numeric()
                            ->default(0)
                            ->prefix('$') // Or dynamically change based on currency
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Credit Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_postpaid')
                            ->label('Enable Post-Paid (Credit)')
                            ->helperText('Allows balance to go negative up to the credit limit.')
                            ->live() // Reactive!
                            ->default(false),

                        Forms\Components\TextInput::make('credit_limit')
                            ->label('Credit Limit')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->visible(fn (Forms\Get $get) => $get('is_postpaid')) // Hide if not postpaid
                            ->required(fn (Forms\Get $get) => $get('is_postpaid')), // Required only if postpaid
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('balance')->money(fn ($record) => $record->currency)->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('currency')->sortable(),
                Tables\Columns\IconColumn::make('is_postpaid')->boolean()->label('Post-Paid'),
                Tables\Columns\TextColumn::make('credit_limit')->money(fn ($record) => $record->currency)->label('Limit'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recharge')
                    ->label('Recharge (Libélula)')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount to Recharge')
                            ->numeric()
                            ->required()
                            ->prefix('BOB')
                            ->minValue(1)
                            ->default(100),
                    ])
                    ->action(function (Wallet $record, array $data) {
                        $service = new \App\Services\LibelulaPaymentService();
                        $result = $service->createPayment($record, $data['amount']);
                        
                        if ($result['success']) {
                            // Redirect to Payment URL
                            // Filament Action typically stays on page, but we can open URL
                            \Filament\Notifications\Notification::make()
                                ->title('Payment Created')
                                ->body('Redirecting to Libélula...')
                                ->success()
                                ->send();
                                
                            // Open URL in new tab using Javascript or redirect
                            // Since this is server side, we can return a redirect? 
                            // Filament actions can assume redirect returns.
                            return redirect()->away($result['payment_url']);
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Payment Failed')
                                ->body($result['error'] ?? 'Unknown error')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Potentially TransactionHistoryRelationManager
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
