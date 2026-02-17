<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TariffResource\Pages;
use App\Models\Tariff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TariffResource extends Resource
{
    protected static ?string $model = Tariff::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Business';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('General Info')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('currency')
                                    ->options([
                                        'USD' => 'USD ($)',
                                        'BOB' => 'BOB (Bs)',
                                        'EUR' => 'EUR (€)',
                                    ])
                                    ->default('USD')
                                    ->required(),
                                Forms\Components\TextInput::make('price_session')
                                    ->label('Connection Fee')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('free_minutes')
                                    ->label('Grace Period')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('min'),
                            ])->columns(2),
                    ])->columnSpan(1),

                Forms\Components\Group::make()
                    ->schema([
                        // BLOCK 1 (Always Visible)
                        Forms\Components\Section::make('Time Block 1 (Default)')
                            ->description('Example: 00:00 - 23:59 (Full Day)')
                            ->schema([
                                Forms\Components\TimePicker::make('b1_start')->default('00:00')->required(),
                                Forms\Components\TimePicker::make('b1_end')->default('23:59')->required(),
                                
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('b1_price_kwh')
                                            ->label('Sell Price ($/kWh)')
                                            ->helperText('Price customer pays.')
                                            ->numeric()
                                            ->default(0),
                                        Forms\Components\TextInput::make('b1_cost_kwh')
                                            ->label('Buy Cost ($/kWh)')
                                            ->helperText('Your cost from utility.')
                                            ->numeric()
                                            ->default(0),
                                        Forms\Components\TextInput::make('b1_price_min')
                                            ->label('Time Fee ($/Min)')
                                            ->numeric()
                                            ->default(0),
                                    ]),
                            ])->columns(2),

                        // BLOCK 2
                        Forms\Components\Section::make('Time Block 2 (Optional)')
                            ->collapsed()
                            ->schema([
                                Forms\Components\TimePicker::make('b2_start'),
                                Forms\Components\TimePicker::make('b2_end'),
                                
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('b2_price_kwh')->label('Sell Price ($/kWh)')->numeric(),
                                        Forms\Components\TextInput::make('b2_cost_kwh')->label('Buy Cost ($/kWh)')->numeric(),
                                        Forms\Components\TextInput::make('b2_price_min')->label('Time Fee ($/Min)')->numeric(),
                                    ]),
                            ])->columns(2),

                        // BLOCK 3
                        Forms\Components\Section::make('Time Block 3 (Optional)')
                            ->collapsed()
                            ->schema([
                                Forms\Components\TimePicker::make('b3_start'),
                                Forms\Components\TimePicker::make('b3_end'),
                                
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('b3_price_kwh')->label('Sell Price ($/kWh)')->numeric(),
                                        Forms\Components\TextInput::make('b3_cost_kwh')->label('Buy Cost ($/kWh)')->numeric(),
                                        Forms\Components\TextInput::make('b3_price_min')->label('Time Fee ($/Min)')->numeric(),
                                    ]),
                            ])->columns(2),

                        // BLOCK 4
                        Forms\Components\Section::make('Time Block 4 (Optional)')
                            ->collapsed()
                            ->schema([
                                Forms\Components\TimePicker::make('b4_start'),
                                Forms\Components\TimePicker::make('b4_end'),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('b4_price_kwh')->label('Sell Price ($/kWh)')->numeric(),
                                        Forms\Components\TextInput::make('b4_cost_kwh')->label('Buy Cost ($/kWh)')->numeric(),
                                        Forms\Components\TextInput::make('b4_price_min')->label('Time Fee ($/Min)')->numeric(),
                                    ]),
                            ])->columns(2),
                    ])->columnSpan(2),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('currency')->sortable(),
                Tables\Columns\TextColumn::make('b1_price_kwh')->label('Base $/kWh')->money(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTariffs::route('/'),
            'create' => Pages\CreateTariff::route('/create'),
            'edit' => Pages\EditTariff::route('/{record}/edit'),
        ];
    }
}
