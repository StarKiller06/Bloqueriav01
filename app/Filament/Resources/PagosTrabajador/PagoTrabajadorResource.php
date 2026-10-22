<?php

namespace App\Filament\Resources\PagosTrabajador;

use App\Actions\Produccion\PagarTrabajador;
use App\Filament\Resources\PagosTrabajador\Pages\ManagePagosTrabajador;
use App\Filament\Support\Reglas;
use App\Models\PagoTrabajador;
use App\Models\Trabajador;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
     * desde esta página se elige. Sugiere bultos pendientes × tarifa, y el monto se puede ajustar.
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
            ->fillForm(fn (?Trabajador $record): array => [
                'trabajador_id' => $record?->id,
                'fecha' => now()->toDateString(),
                'monto' => $record ? PagarTrabajador::montoSugerido($record) : null,
            ])
            ->schema([
                Select::make('trabajador_id')
                    ->label('Trabajador')
                    ->options(fn (): array => Trabajador::query()
                        ->whereHas('producciones', fn (Builder $query) => $query->sinPagar())
                        ->orderBy('nombre')
                        ->pluck('nombre', 'id')
                        ->all())
                    ->required()
                    ->hidden($conTrabajador)
                    ->live()
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('monto', $state
                        ? PagarTrabajador::montoSugerido(Trabajador::findOrFail($state))
                        : null)),
                DatePicker::make('fecha')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                TextInput::make('monto')
                    ->numeric()
                    ->minValue(1)
                    ->prefix('$')
                    ->required()
                    ->helperText(fn (Get $get): ?string => static::detalle($get('trabajador_id'))),
            ])
            ->action(function (array $data, Action $action, PagarTrabajador $pagar, ?Trabajador $record): void {
                $trabajador = $record ?? Trabajador::findOrFail($data['trabajador_id']);

                Reglas::aplicar($action, fn () => $pagar($trabajador, Carbon::parse($data['fecha']), $data['monto'], auth()->user()));
            })
            ->successNotificationTitle('Pago registrado y descontado de la caja');
    }

    private static function detalle(mixed $trabajadorId): ?string
    {
        $trabajador = filled($trabajadorId) ? Trabajador::find($trabajadorId) : null;

        if ($trabajador === null) {
            return null;
        }

        return sprintf(
            '%d bultos pendientes × $%s por bulto. Los bloques de más no cambian el pago.',
            $trabajador->bultosPorPagar(),
            number_format((float) $trabajador->empresa->tarifa_por_bulto, 0, ',', '.'),
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
