<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers\PaymentsRelationManager;
use App\Models\Sale;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction; // Importar la acción de ver
use Filament\Tables\Actions\EditAction; // Importar la acción de editar
use Filament\Tables\Actions\DeleteAction; // Importar la acción de eliminar
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Textarea;


class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Ventas y Comisiones';
    protected static ?string $modelLabel = 'Venta'; // Nombre del modelo en singular
    protected static ?string $pluralModelLabel = 'Ventas'; // Nombre del modelo en plural

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Vendedor')
                    ->default(Auth::id())
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(),
                Select::make('client_id')
                    ->relationship('client', 'name')
                    ->label('Cliente')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('product')
                    ->label('Producto')
                    ->required()
                    ->columnSpan('full'),
                Textarea::make('description')
                    ->label('Descripción') // Etiqueta traducida
                    ->columnSpan('full')    
                    ->rows(10),
                TextInput::make('total_amount')
                    ->label('Monto total')
                    ->numeric()
                    ->required()
                    ->columnSpan("full"),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name') // Agrega esta columna para ver el vendedor
                    ->label('Vendedor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Monto total')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Monto pagado')
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('canjeado')
                    ->label('Canjeado')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha de Venta')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                //ViewAction::make(), // Agrega el botón para ver el registro
                EditAction::make(), // Agrega el botón para editar el registro
                DeleteAction::make(), // Agrega el botón para eliminar el registro
            ])
            ->bulkActions([
                // Eliminamos la acción masiva de aquí
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->currentUser();
    }
}