<?php

namespace App\Filament\Resources\MovimientosCaja\Pages;

use App\Actions\Caja\RegistrarMovimiento;
use App\Enums\TipoMovimiento;
use App\Filament\Resources\MovimientosCaja\MovimientoCajaResource;
use App\Filament\Widgets\ResumenCaja;
use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Carbon;

class ManageMovimientosCaja extends ManageRecords
{
    protected static string $resource = MovimientoCajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->registrarAction(TipoMovimiento::Entrada),
            $this->registrarAction(TipoMovimiento::Gasto),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResumenCaja::class,
        ];
    }

    private function registrarAction(TipoMovimiento $tipo): CreateAction
    {
        $nombre = $tipo === TipoMovimiento::Entrada ? 'entrada' : 'gasto';

        return CreateAction::make("registrar_{$nombre}")
            ->label("Registrar {$nombre}")
            ->modalHeading("Registrar {$nombre}")
            ->icon($tipo->getIcon())
            ->color($tipo->getColor())
            ->schema(MovimientoCajaResource::campos($tipo))
            ->using(fn (array $data, RegistrarMovimiento $registrar): MovimientoCaja => $registrar(
                concepto: ConceptoCaja::findOrFail($data['concepto_caja_id']),
                monto: $data['monto'],
                fecha: Carbon::parse($data['fecha']),
                user: auth()->user(),
                sucursal: filled($data['sucursal_id'] ?? null) ? Sucursal::findOrFail($data['sucursal_id']) : null,
                nota: $data['nota'] ?? null,
            ))
            ->successNotificationTitle($tipo === TipoMovimiento::Entrada ? 'Entrada registrada' : 'Gasto registrado');
    }
}
