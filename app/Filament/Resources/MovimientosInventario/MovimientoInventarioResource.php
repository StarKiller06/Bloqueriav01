<?php

namespace App\Filament\Resources\MovimientosInventario;

use App\Enums\ItemInventario;
use App\Enums\TipoMovimientoInventario;
use App\Filament\Resources\MovimientosInventario\Pages\ManageMovimientosInventario;
use App\Models\MovimientoInventario;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * El "kárdex": cada entrada y salida de bloques y cemento, con su origen.
 */
class MovimientoInventarioResource extends Resource
{
    protected static ?string $model = MovimientoInventario::class;

    protected static ?string $slug = 'inventario';

    protected static ?string $modelLabel = 'movimiento de inventario';

    protected static ?string $pluralModelLabel = 'movimientos de inventario';

    protected static ?string $navigationLabel = 'Inventario';

    protected static string|UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['sucursal', 'user']))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('fecha')->orderByDesc('id'))
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal'),
                TextColumn::make('item')
                    ->label('Ítem')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('tipo')
                    ->badge(),
                TextColumn::make('cantidad')
                    ->numeric()
                    ->formatStateUsing(fn (int $state, MovimientoInventario $record): string => sprintf(
                        '%s%s %s',
                        $state > 0 ? '+' : '−',
                        number_format(abs($state), 0, ',', '.'),
                        $record->item->unidad(),
                    ))
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->alignEnd(),
                TextColumn::make('nota')
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label('Registró')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sucursal')
                    ->relationship('sucursal', 'nombre'),
                SelectFilter::make('item')
                    ->label('Ítem')
                    ->options(ItemInventario::class),
                SelectFilter::make('tipo')
                    ->options(TipoMovimientoInventario::class),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $query, string $desde) => $query->whereDate('fecha', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $query, string $hasta) => $query->whereDate('fecha', '<=', $hasta))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMovimientosInventario::route('/'),
        ];
    }
}
