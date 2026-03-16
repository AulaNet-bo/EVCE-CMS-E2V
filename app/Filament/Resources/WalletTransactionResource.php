<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Filament\Resources\WalletTransactionResource\RelationManagers;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Transactions';

    public static function form(Form $form): Form
    {
        $isAdminOrAccountant = fn() => auth()->user()?->hasRole(['super_admin', 'system_accountant']);

        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('type')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('BOB')
                    ->disabled(!$isAdminOrAccountant()),
                Forms\Components\Select::make('status')
                    ->options([
                        'PENDING' => 'Pendiente',
                        'COMPLETED' => 'Completado',
                        'FAILED' => 'Fallido',
                    ])
                    ->disabled(!$isAdminOrAccountant()),
                Forms\Components\FileUpload::make('payment_evidence_path')
                    ->label('Evidencia de Pago')
                    ->image()
                    ->directory('payment-evidence')
                    ->visibility('private')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->disabled(!$isAdminOrAccountant()),
                Forms\Components\TextInput::make('external_payment_id')
                    ->label('Gateway ID')
                    ->disabled(),
                Forms\Components\TextInput::make('invoice_number')
                    ->label('Factura #')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'RECHARGE' => 'success',
                        'CHARGE' => 'danger',
                        'REFUND' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->weight('bold')
                    ->color(fn($record) => $record->type === 'RECHARGE' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance')
                    ->money(fn($record) => $record->currency)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'COMPLETED' => 'success',
                        'PENDING' => 'warning',
                        'FAILED' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->limit(30),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Adjuntar Evidencia'),
                Tables\Actions\Action::make('validate_payment')
                    ->label('Validar Pago')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn(WalletTransaction $record): bool =>
                        $record->status === 'PENDING' &&
                        $record->type === 'RECHARGE' &&
                        auth()->user()->hasRole(['super_admin', 'system_accountant'])
                    )
                    ->action(function (WalletTransaction $record) {
                        DB::transaction(function () use ($record) {
                            $wallet = $record->wallet;
                            $wallet->increment('balance', $record->amount);

                            $record->update([
                                'status' => 'COMPLETED',
                                'balance_after' => $wallet->balance,
                            ]);
                        });

                        Notification::make()
                            ->title('Pago Validado')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'create' => Pages\CreateWalletTransaction::route('/create'),
            'edit' => Pages\EditWalletTransaction::route('/{record}/edit'),
        ];
    }
}
