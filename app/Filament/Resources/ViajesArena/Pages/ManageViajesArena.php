<?php

namespace App\Filament\Resources\ViajesArena\Pages;

use App\Actions\Inventario\RegistrarViajeArena;
use App\Filament\Resources\ViajesArena\ViajeArenaResource;
use App\Filament\Support\Reglas;
use App\Models\Sucursal;
use App\Models\ViajeArena;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Carbon;

class ManageViajesArena extends ManageRecords
{
    protected static string $resource = ViajeArenaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('registrar')
                ->label('Registrar viaje')
                ->modalHeading('Viaje de arena')
                ->modalDescription('El costo se descuenta de la caja y la arena alcanza para los bloques estimados.')
                ->schema([
                    Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->options(fn (): array => Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                        ->required(),
                    DatePicker::make('fecha')
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    TextInput::make('metros_cubicos')
                        ->label('Metros cúbicos')
                        ->numeric()
                        ->minValue(0.5)
                        ->default(RegistrarViajeArena::METROS_POR_VIAJE)
                        ->suffix('m³')
                        ->required(),
                    TextInput::make('costo')
                        ->numeric()
                        ->minValue(1)
                        ->prefix('$')
                        ->required(),
                ])
                ->using(fn (array $data, CreateAction $action, RegistrarViajeArena $registrar): ViajeArena => Reglas::aplicar($action, fn (): ViajeArena => $registrar(
                    sucursal: Sucursal::findOrFail($data['sucursal_id']),
                    fecha: Carbon::parse($data['fecha']),
                    costo: $data['costo'],
                    metrosCubicos: $data['metros_cubicos'],
                    user: auth()->user(),
                )))
                ->successNotificationTitle('Viaje registrado'),
        ];
    }
}
