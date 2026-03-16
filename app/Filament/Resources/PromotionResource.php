<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromotionResource\Pages;
use App\Filament\Resources\PromotionResource\RelationManagers;
use App\Models\Promotion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Promociones y Alertas';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?string $pluralLabel = 'Promociones';
    protected static ?string $modelLabel = 'Promoción';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contenido de la Promoción')
                    ->description('Defina el mensaje y la imagen que verán los usuarios.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('body')
                            ->label('Descripción / Mensaje')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Imagen Publicitaria')
                            ->image()
                            ->directory('promotions')
                            ->visibility('public'),
                    ])->columns(1),

                Forms\Components\Section::make('Configuración de Visualización')
                    ->description('Defina cómo y cuándo aparecerá esta promoción.')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Notificación')
                            ->options([
                                'push' => 'Notificación Push (StatusBar)',
                                'in_app' => 'Mensaje en la Campanita (In-App)',
                                'alert' => 'Alerta Popup (Al abrir la App)',
                            ])
                            ->required()
                            ->default('push'),
                        Forms\Components\Select::make('frequency')
                            ->label('Frecuencia de Visualización')
                            ->options([
                                'every_open' => 'Cada vez que abra la app',
                                'daily' => 'Una vez al día',
                                'once_total' => 'Solo una vez',
                            ])
                            ->required()
                            ->default('daily'),
                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('Fecha de Inicio')
                            ->helperText('Opcional. Si se deja vacío, inicia de inmediato.'),
                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('Fecha de Finalización')
                            ->helperText('Opcional. Si se deja vacío, no caduca.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('¿Activa?')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'push' => 'danger',
                        'in_app' => 'info',
                        'alert' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Frecuencia'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}
