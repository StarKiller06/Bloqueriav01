<?php

namespace App\Actions\Produccion;

use App\Actions\Caja\RegistrarMovimiento;
use App\Enums\ConceptoSistema;
use App\Exceptions\ReglaDeNegocio;
use App\Models\PagoTrabajador;
use App\Models\Trabajador;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Paga al trabajador todas sus producciones pendientes, por bulto. Los bloques de más
 * no cambian el pago. El monto sugerido se puede ajustar, y sale de la caja.
 */
class PagarTrabajador
{
    public function __construct(private RegistrarMovimiento $moverCaja) {}

    /**
     * Lo que le correspondería hoy: bultos pendientes × tarifa por bulto de la empresa.
     */
    public static function montoSugerido(Trabajador $trabajador): float
    {
        return $trabajador->bultosPorPagar() * (float) $trabajador->empresa->tarifa_por_bulto;
    }

    public function __invoke(
        Trabajador $trabajador,
        CarbonInterface $fecha,
        string|float|null $monto = null,
        ?User $user = null,
    ): PagoTrabajador {
        return DB::transaction(function () use ($trabajador, $fecha, $monto, $user): PagoTrabajador {
            $pendientes = $trabajador->producciones()->sinPagar()->lockForUpdate()->get();

            if ($pendientes->isEmpty()) {
                throw new ReglaDeNegocio("{$trabajador->nombre} no tiene producción pendiente por pagar.");
            }

            $bultos = (int) $pendientes->sum('bultos_usados');
            $tarifa = (float) $trabajador->empresa->tarifa_por_bulto;
            $monto ??= $bultos * $tarifa;

            if ((float) $monto <= 0) {
                throw new ReglaDeNegocio('El monto del pago debe ser mayor que cero. Revisa la tarifa por bulto en los datos de la bloquería.');
            }

            $pago = new PagoTrabajador([
                'trabajador_id' => $trabajador->getKey(),
                'fecha' => $fecha,
                'bultos_pagados' => $bultos,
                'tarifa' => $tarifa,
                'monto' => $monto,
                'user_id' => $user?->getKey(),
            ]);
            $pago->empresa()->associate($trabajador->empresa_id);
            $pago->save();

            $trabajador->producciones()->whereKey($pendientes->modelKeys())->update(['pago_trabajador_id' => $pago->getKey()]);

            ($this->moverCaja)(
                $trabajador->empresa->conceptoSistema(ConceptoSistema::PagoTrabajador), $monto, $fecha,
                user: $user, sucursal: $trabajador->sucursal, nota: "{$trabajador->nombre}: {$bultos} bultos", origen: $pago,
            );

            return $pago;
        });
    }
}
