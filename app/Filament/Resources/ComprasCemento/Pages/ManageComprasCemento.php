<?php

namespace App\Filament\Resources\ComprasCemento\Pages;

use App\Actions\Inventario\RegistrarCompraCemento;
use App\Filament\Resources\ComprasCemento\CompraCementoResource;
use App\Filament\Support\Reglas;
use App\Models\CompraCemento;
use App\Models\Empresa;
use App\Models\Sucursal;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;

class ManageComprasCemento extends ManageRecords
{
    protected static string $resource = CompraCementoResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Empresa $empresa */
        $empresa = Filament::getTenant();

        return [
            CreateAction::make('registrar')
                ->label('Registrar compra')
                ->modalHeading('Compra de cemento')
                ->modalDescription('Se paga de la caja y los bultos se reparten entre las sucursales.')
                ->schema([
                    DatePicker::make('fecha')
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    TextInput::make('proveedor')
                        ->maxLength(255),
                    TextInput::make('bultos')
                        ->label('Bultos comprados')
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->live(debounce: 400),
                    TextInput::make('precio_bulto')
                        ->label('Precio por bulto')
                        ->numeric()
                        ->minValue(1)
                        ->prefix('$')
                        ->required(),
                    Repeater::make('reparto')
                        ->label('Reparto entre sucursales')
                        ->schema([
                            Select::make('sucursal_id')
                                ->label('Sucursal')
                                ->options(fn (): array => Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),
                            TextInput::make('bultos')
                                ->integer()
                                ->minValue(0)
                                ->required()
                                ->live(debounce: 400),
                        ])
                        ->columns(2)
                        ->default(fn (): array => $empresa->sucursales()->orderBy('nombre')->get()
                            ->map(fn (Sucursal $sucursal): array => ['sucursal_id' => $sucursal->id, 'bultos' => 0])
                            ->all())
                        ->minItems(1)
                        ->addActionLabel('Agregar sucursal')
                        ->helperText(fn (Get $get): string => sprintf(
                            'Repartidos: %d de %d bultos.',
                            collect($get('reparto') ?? [])->sum(fn (array $fila): int => (int) ($fila['bultos'] ?? 0)),
                            (int) $get('bultos'),
                        )),
                ])
                ->using(fn (array $data, CreateAction $action, RegistrarCompraCemento $registrar): CompraCemento => Reglas::aplicar($action, fn (): CompraCemento => $registrar(
                    empresa: $empresa,
                    fecha: Carbon::parse($data['fecha']),
                    bultos: (int) $data['bultos'],
                    precioBulto: $data['precio_bulto'],
                    reparto: collect($data['reparto'])->mapWithKeys(fn (array $fila): array => [(int) $fila['sucursal_id'] => (int) $fila['bultos']])->all(),
                    proveedor: $data['proveedor'] ?? null,
                    user: auth()->user(),
                )))
                ->successNotificationTitle('Compra registrada'),
        ];
    }
}
