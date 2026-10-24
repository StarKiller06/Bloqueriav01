<?php

namespace App\Filament\Resources\PagosTrabajador;

use App\Actions\Produccion\PagarTrabajador;
use App\Filament\Resources\PagosTrabajador\Pages\ManagePagosTrabajador;
use App\Filament\Support\Reglas;
use App\Models\PagoTrabajador;
use App\Models\Produccion;
use App\Models\Trabajador;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use UnitEnum;

class PagoTrabajadorResource extends Resource
{
    protected static ?string $model = PagoTrabajador::class;

    protected static ?string $slug = 'pagos-trabajadores';

    protected static ?string $modelLabel = 'pago a trabajador';

    protected static ?string $pluralModelLabel = 'pagos a trabajadores';

    protected static string|UnitEnum|null $navigationGroup = 'Producción';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    /**
     * Botón "Pagar". Desde la lista de trabajadores ya viene con el trabajador elegido;
     * desde esta página se elige. Se marcan los días de producción a pagar (todos, por defecto)
     * y el monto se calcula solo: bultos de esos días × tarifa por bulto.
     */
    public static function pagarAction(bool $conTrabajador = false): Action
    {
        return Action::make('pagar')
            ->label($conTrabajador ? 'Pagar' : 'Pagar a un trabajador')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->color('success')
            ->authorize(fn (): bool => auth()->user()->can('create', PagoTrabajador::class))
            ->visible(fn (?Trabajador $record): bool => ! $conTrabajador || $record?->bultosPorPagar() > 0)
            ->modalHeading(fn (?Trabajador $record): string => $record ? "Pagar a {$record->nombre}" : 'Pagar a un trabajador')
            ->modalDescription('El cemento de estos bultos ya se descontó del inventario al registrar la producción; pagar no lo vuelve a descontar.')
            ->fillForm(fn (?Trabajador $record): array => [
                'trabajador_id' => $record?->id,
                'fecha' => now()->toDateString(),
                'producciones' => $record ? static::pendientes($record)->keys()->all() : [],
            ])
            ->schema([
                Select::make('trabajador_id')
                    ->label('Trabajador')
                    ->options(fn (): array => static::bultosPendientesPorTrabajador()
                        ->map(fn (Trabajador $trabajador): string => $trabajador->bultos_pendientes > 0
                            ? "{$trabajador->nombre} · {$trabajador->bultos_pendientes} bultos por pagar"
                            : "{$trabajador->nombre} · nada pendiente")
                        ->all())
                    ->disableOptionWhen(fn (string $value): bool => (int) (static::bultosPendientesPorTrabajador()[(int) $value]?->bultos_pendientes ?? 0) === 0)
                    ->helperText(fn (): ?string => static::bultosPendientesPorTrabajador()->every(fn (Trabajador $trabajador): bool => (int) $trabajador->bultos_pendientes === 0)
                        ? 'Nadie tiene producción pendiente por pagar. Primero registra lo que sacó cada trabajador en Producción → Registrar producción.'
                        : null)
                    ->required()
                    ->hidden($conTrabajador)
                    ->live()
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('producciones', $state
                        ? static::pendientes(Trabajador::findOrFail($state))->keys()->all()
                        : [])),
                CheckboxList::make('producciones')
                    ->label('Días de producción a pagar')
                    ->options(fn (Get $get, ?Trabajador $record): array => ($trabajador = $record ?? Trabajador::find($get('trabajador_id')))
                        ? static::pendientes($trabajador)->all()
                        : [])
                    ->required()
                    ->bulkToggleable()
                    ->live()
                    ->helperText(fn (Get $get, ?Trabajador $record): ?string => static::resumen($record ?? Trabajador::find($get('trabajador_id')), $get('producciones') ?? [])),
                DatePicker::make('fecha')
                    ->label('Fecha del pago')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
            ])
            ->action(function (array $data, Action $action, PagarTrabajador $pagar, ?Trabajador $record): void {
                $trabajador = $record ?? Trabajador::findOrFail($data['trabajador_id']);

                Reglas::aplicar($action, fn () => $pagar(
                    $trabajador,
                    Carbon::parse($data['fecha']),
                    array_map('intval', $data['producciones']),
                    auth()->user(),
                ));
            })
            ->successNotificationTitle('Pago registrado y descontado de la caja');
    }

    /**
     * Trabajadores activos con los bultos que se les deben, para elegir a quién pagar.
     *
     * @return Collection<int, Trabajador>
     */
    private static function bultosPendientesPorTrabajador(): Collection
    {
        return once(fn (): Collection => Trabajador::query()
            ->activos()
            ->withSum(['producciones as bultos_pendientes' => fn (Builder $query) => $query->sinPagar()], 'bultos_usados')
            ->orderBy('nombre')
            ->get()
            ->keyBy('id'));
    }

    /**
     * Días pendientes del trabajador, listos para mostrar: "03/10 · 4 bultos · 412 bloques".
     *
     * @return Collection<int, string>
     */
    private static function pendientes(Trabajador $trabajador): Collection
    {
        return $trabajador->producciones()->sinPagar()->orderBy('fecha')->orderBy('id')->get()
            ->mapWithKeys(fn (Produccion $produccion): array => [$produccion->id => sprintf(
                '%s · %d bultos · %s bloques',
                $produccion->fecha->format('d/m/Y'),
                $produccion->bultos_usados,
                number_format($produccion->bloques_sacados, 0, ',', '.'),
            )]);
    }

    /**
     * @param  array<int|string>  $elegidas
     */
    private static function resumen(?Trabajador $trabajador, array $elegidas): ?string
    {
        if ($trabajador === null) {
            return null;
        }

        $tarifa = (float) $trabajador->empresa->tarifa_por_bulto;

        if ($tarifa <= 0) {
            return 'La tarifa por bulto está en $0: configúrala en "Datos de la bloquería".';
        }

        $bultos = (int) $trabajador->producciones()->sinPagar()->whereKey($elegidas)->sum('bultos_usados');

        return sprintf(
            'A pagar: %d bultos × %s = %s',
            $bultos,
            Number::currency($tarifa, 'COP', 'es_CO', 0),
            Number::currency($bultos * $tarifa, 'COP', 'es_CO', 0),
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['trabajador', 'user']))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('fecha')->orderByDesc('id'))
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('trabajador.nombre')
                    ->label('Trabajador')
                    ->searchable(),
                TextColumn::make('bultos_pagados')
                    ->label('Bultos')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('tarifa')
                    ->label('Tarifa por bulto')
                    ->money(decimalPlaces: 0)
                    ->alignEnd(),
                TextColumn::make('monto')
                    ->money(decimalPlaces: 0)
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total')->money('COP', locale: 'es_CO', decimalPlaces: 0)),
                TextColumn::make('user.name')
                    ->label('Registró')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('trabajador')
                    ->relationship('trabajador', 'nombre')
                    ->searchable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePagosTrabajador::route('/'),
        ];
    }
}
