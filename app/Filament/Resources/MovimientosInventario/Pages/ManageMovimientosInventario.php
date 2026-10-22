<?php

namespace App\Filament\Resources\MovimientosInventario\Pages;

use App\Actions\Inventario\AjustarInventario;
use App\Actions\Inventario\TrasladarBloques;
use App\Enums\ItemInventario;
use App\Filament\Resources\MovimientosInventario\MovimientoInventarioResource;
use App\Filament\Support\Reglas;
use App\Filament\Widgets\ResumenInventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class ManageMovimientosInventario extends ManageRecords
{
    protected static string $resource = MovimientoInventarioResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ResumenInventario::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->trasladarAction(),
            $this->ajustarAction(),
        ];
    }

    private function trasladarAction(): Action
    {
        return Action::make('trasladar')
            ->label('Trasladar bloques')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->schema([
                Select::make('origen_id')
                    ->label('Desde')
                    ->options(fn (): array => $this->sucursales())
                    ->required()
                    ->live(),
                Select::make('destino_id')
                    ->label('Hacia')
                    ->options(fn (Get $get): array => collect($this->sucursales())->except($get('origen_id'))->all())
                    ->required()
                    ->different('origen_id'),
                Select::make('producto_id')
                    ->label('Producto')
                    ->options(fn (): array => Producto::query()->pluck('nombre', 'id')->all())
                    ->default(fn (): ?int => Producto::query()->count() === 1 ? Producto::query()->value('id') : null)
                    ->required(),
                TextInput::make('cantidad')
                    ->label('Bloques')
                    ->integer()
                    ->minValue(1)
                    ->required()
                    ->helperText(fn (Get $get): ?string => filled($get('origen_id'))
                        ? 'Hay '.Sucursal::findOrFail($get('origen_id'))->stock(ItemInventario::Bloque).' bloques en el origen.'
                        : null),
                DatePicker::make('fecha')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                TextInput::make('nota')
                    ->maxLength(255),
            ])
            ->action(fn (array $data, Action $action, TrasladarBloques $trasladar) => Reglas::aplicar($action, fn () => $trasladar(
                origen: Sucursal::findOrFail($data['origen_id']),
                destino: Sucursal::findOrFail($data['destino_id']),
                producto: Producto::findOrFail($data['producto_id']),
                cantidad: (int) $data['cantidad'],
                fecha: Carbon::parse($data['fecha']),
                user: auth()->user(),
                nota: $data['nota'] ?? null,
            )))
            ->successNotificationTitle('Traslado registrado');
    }

    private function ajustarAction(): Action
    {
        return Action::make('ajustar')
            ->label('Ajustar inventario')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->authorize(fn (): bool => auth()->user()->can('create', MovimientoInventario::class))
            ->modalDescription('Úsalo cuando el conteo físico no coincida con el sistema. Queda registrado quién lo hizo y por qué.')
            ->schema([
                Select::make('sucursal_id')
                    ->label('Sucursal')
                    ->options(fn (): array => $this->sucursales())
                    ->required(),
                Select::make('item')
                    ->label('Ítem')
                    ->options(ItemInventario::class)
                    ->required()
                    ->live(),
                Select::make('producto_id')
                    ->label('Producto')
                    ->options(fn (): array => Producto::query()->pluck('nombre', 'id')->all())
                    ->default(fn (): ?int => Producto::query()->count() === 1 ? Producto::query()->value('id') : null)
                    ->visible(fn (Get $get): bool => $get('item') === ItemInventario::Bloque || $get('item') === ItemInventario::Bloque->value)
                    ->required(),
                TextInput::make('cantidad')
                    ->integer()
                    ->notIn([0])
                    ->required()
                    ->helperText('Positivo para sumar, negativo para restar. Ejemplo: −2 si faltan dos bultos.'),
                DatePicker::make('fecha')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                TextInput::make('nota')
                    ->label('Motivo')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(fn (array $data, Action $action, AjustarInventario $ajustar) => Reglas::aplicar($action, fn () => $ajustar(
                sucursal: Sucursal::findOrFail($data['sucursal_id']),
                item: $data['item'] instanceof ItemInventario ? $data['item'] : ItemInventario::from($data['item']),
                cantidad: (int) $data['cantidad'],
                nota: $data['nota'],
                fecha: Carbon::parse($data['fecha']),
                producto: filled($data['producto_id'] ?? null) ? Producto::findOrFail($data['producto_id']) : null,
                user: auth()->user(),
            )))
            ->successNotificationTitle('Inventario ajustado');
    }

    /**
     * @return array<int, string>
     */
    private function sucursales(): array
    {
        return Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all();
    }
}
