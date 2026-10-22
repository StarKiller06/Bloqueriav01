<?php

namespace App\Filament\Resources\Productos\RelationManagers;

use App\Models\HistorialPrecio;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Solo lectura: el historial se llena con la acción "Ajustar precio".
 */
class HistorialPreciosRelationManager extends RelationManager
{
    protected static string $relationship = 'historialPrecios';

    protected static ?string $title = 'Historial de precios';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('cambio de precio')
            ->pluralModelLabel('cambios de precio')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y h:i a'),
                TextColumn::make('precio_anterior')
                    ->label('Antes')
                    ->money(decimalPlaces: 0),
                TextColumn::make('precio_nuevo')
                    ->label('Después')
                    ->money(decimalPlaces: 0),
                TextColumn::make('variacion')
                    ->label('Variación')
                    ->state(fn (HistorialPrecio $record): float => (float) $record->precio_nuevo - (float) $record->precio_anterior)
                    ->money(decimalPlaces: 0)
                    ->color(fn (float $state): string => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('user.name')
                    ->label('Cambiado por')
                    ->placeholder('Sistema'),
            ])
            ->emptyStateHeading('Aún no se ha cambiado el precio');
    }
}
