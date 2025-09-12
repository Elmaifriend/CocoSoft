<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BonusResource\Pages;
use App\Filament\Resources\BonusResource\RelationManagers\SalesRelationManager;
use App\Models\Bonus;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BonusResource extends Resource
{
    protected static ?string $model = Bonus::class;
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Bonos';
    protected static ?string $pluralModelLabel = 'Bonos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Este recurso es de solo lectura para el usuario final
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->label('Monto del Bono')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('sales_count')
                    ->label('Ventas Asociadas')
                    ->counts('sales')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Agregamos una acción de visualización para ver los detalles del bono.
                \Filament\Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SalesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBonuses::route('/'),
            //'view' => Pages\ViewBonus::route('/{record}'), // Agregamos una página de visualización.
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->currentUser();
    }
}