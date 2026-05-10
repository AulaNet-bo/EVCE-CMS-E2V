<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Gestión Comercial';
    protected static ?string $modelLabel = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Producto')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Producto')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('siat_product_code')
                            ->label('Código SIN / SIAT')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('internal_code')
                            ->label('Código Interno')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('price')
                            ->label('Precio Unitario (BOB)')
                            ->numeric()
                            ->prefix('Bs.')
                            ->helperText('Dejar en blanco si el precio es variable (ej. Energía)'),
                        Forms\Components\Select::make('unit_of_measure')
                            ->label('Unidad de Medida')
                            ->options([
                                'UNIDAD' => 'UNIDAD',
                                'SERVICIO' => 'SERVICIO',
                                'KWH' => 'Kilovatios/Hora (KWH)',
                            ])
                            ->required()
                            ->default('UNIDAD'),
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Producto')
                            ->options([
                                'fixed' => 'Físico / Precio Fijo',
                                'service' => 'Servicio / Precio Variable',
                            ])
                            ->required()
                            ->default('fixed'),
                        Forms\Components\TextInput::make('category')
                            ->label('Categoría')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Está Activo')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('siat_product_code')
                    ->label('Cod. SIN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('internal_code')
                    ->label('Cod. Interno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('BOB')
                    ->placeholder('Variable'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'success',
                        'service' => 'warning',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
