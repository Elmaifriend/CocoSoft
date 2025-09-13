<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Client;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Ventas y Comisiones';

    protected static ?string $modelLabel = 'Pago';
    protected static ?string $pluralModelLabel = 'Pagos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('client_id')
                ->label('Cliente')
                ->options(Client::pluck('name', 'id'))
                ->live()
                ->required()
                ->searchable()
                ->preload()
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('sale_id', null);
                }),

            Select::make('sale_id')
                ->label('Venta / Paquete')
                ->options(function (callable $get) {
                    $clientId = $get('client_id');
                    if (!$clientId) {
                        return [];
                    }
                    
                    // Obtiene todas las ventas del cliente para el filtro en PHP
                    $sales = Sale::where('client_id', $clientId)->get();

                    return $sales->filter(function ($sale) {
                        return $sale->total_amount > $sale->paid_amount;
                    })->pluck('product', 'id');
                })
                ->required()
                ->searchable()
                ->preload(),

            TextInput::make('amount')
                ->label('Monto del pago')
                ->required()
                ->numeric()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->label('ID')
                ->sortable(),

            TextColumn::make('sale.product')
                ->label('Venta'),
            
            TextColumn::make('sale.client.name')
                ->label('Cliente'),

            TextColumn::make('amount')
                ->label('Monto')
                ->money('MXN'),

            TextColumn::make('created_at')
                ->label('Fecha de creación')
                ->dateTime(),

            TextColumn::make('updated_at')
                ->label('Última actualización')
                ->dateTime(),
        ])
        ->actions([
            EditAction::make(),
            DeleteAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}