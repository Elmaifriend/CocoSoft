<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Sale;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Ventas y Comisiones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('sale_id')
                    ->label('Venta')
                    ->options(Sale::where('user_id', Auth::id())->pluck('id', 'id'))
                    ->searchable()
                    ->required()
                    ->preload(),
                TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale.id')
                    ->label('ID de Venta')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Monto del Pago')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha del Pago')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
        ];
    }
}