<?php

namespace App\Filament\Resources\BonusResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Monto Total')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Monto Pagado')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('canjeado')
                    ->label('Canjeado')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No permitimos crear ventas desde aquí
            ])
            ->actions([
                // No permitimos editar o borrar ventas desde aquí
            ])
            ->bulkActions([
                //
            ]);
    }
}