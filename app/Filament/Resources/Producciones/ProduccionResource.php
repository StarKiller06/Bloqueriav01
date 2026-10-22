<?php

namespace App\Filament\Resources\Producciones;

use App\Actions\Produccion\AnularProduccion;
use App\Filament\Resources\Producciones\Pages\ManageProducciones;
use App\Filament\Support\Reglas;
use App\Models\Produccion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProduccionResource extends Resource
{
    protected static ?string $model = Produccion::class;

    protected static ?string $slug = 'produccion';

    protected static ?string $modelLabel = 'producción';

    protected static ?string $pluralModelLabel = 'producción';

    protected static ?string $navigationLabel = 'Producción';

    protected static string|UnitEnum|null $navigationGroup = 'Producción';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    /**
     * Verde si sacó de más, rojo si sacó de menos.
     */
    public static function colorDiferencia(int $diferencia): string
    {
        return match (true) {
            $diferencia > 0 => 'success',
            $diferencia < 0 => 'danger',
            default => 'gray',
        };
    }

    public static function formatoDiferencia(int $diferencia): string
    {
        return $diferencia > 0 ? "+{$diferencia}" : (string) $diferencia;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['trabajador', 'sucursal']))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('fecha')->orderByDesc('id'))
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('trabajador.nombre')
                    ->label('Trabajador')
                    ->searchable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal'),
                TextColumn::make('bultos_usados')
                    ->label('Bultos')
                    ->numeric()
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('bloques_esperados')
                    ->label('Esperado')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('bloques_sacados')
                    ->label('Sacados')
                    ->numeric()
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('diferencia')
                    ->badge()
                    ->color(fn (int $state): string => static::colorDiferencia($state))
                    ->formatStateUsing(fn (int $state): string => static::formatoDiferencia($state))
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Neto')),
                IconColumn::make('pago_trabajador_id')
                    ->label('Pagada')
                    ->state(fn (Produccion $record): bool => $record->estaPagada())
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('sucursal')
                    ->relationship('sucursal', 'nombre'),
                SelectFilter::make('trabajador')
                    ->relationship('trabajador', 'nombre')
                    ->searchable(),
                TernaryFilter::make('pagada')
                    ->nullable()
                    ->attribute('pago_trabajador_id'),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $query, string $desde) => $query->whereDate('fecha', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $query, string $hasta) => $query->whereDate('fecha', '<=', $hasta))),
            ])
            ->recordActions([
                Action::make('anular')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->authorize('delete')
                    ->requiresConfirmation()
                    ->modalDescription('Se devuelve el cemento a la sucursal y se retiran los bloques del inventario.')
                    ->action(fn (Produccion $record, Action $action, AnularProduccion $anular) => Reglas::aplicar($action, fn () => $anular($record)))
                    ->successNotificationTitle('Producción anulada'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProducciones::route('/'),
        ];
    }
}
