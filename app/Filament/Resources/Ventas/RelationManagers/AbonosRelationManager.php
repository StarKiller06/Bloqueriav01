<?php

namespace App\Filament\Resources\Ventas\RelationManagers;

use App\Enums\FormaPago;
use App\Models\Venta;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Solo lectura: los abonos se registran con el botón "Abonar".
 */
class AbonosRelationManager extends RelationManager
{
    protected static string $relationship = 'abonos';

    protected static ?string $title = 'Abonos';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var Venta $ownerRecord */
        return $ownerRecord->forma_pago === FormaPago::Credito;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('abono')
            ->pluralModelLabel('abonos')
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y'),
                TextColumn::make('monto')
                    ->money(decimalPlaces: 0)
                    ->summarize(Sum::make()->label('Abonado')->money('COP', locale: 'es_CO', decimalPlaces: 0)),
                TextColumn::make('nota')
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label('Registró'),
            ])
            ->emptyStateHeading('Aún no hay abonos');
    }
}
