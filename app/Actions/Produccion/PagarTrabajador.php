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
 * Paga al trabajador por bulto los días de producción elegidos (o todos los pendientes).
 * El monto siempre es bultos × tarifa por bulto, así que bultos y monto coinciden.
 * Los bloques de más no cambian el pago, y pagar no toca el cemento: ese ya se
 * descontó al registrar la producción.
 */
class PagarTrabajador
{
    public function __construct(private RegistrarMovimiento $moverCaja) {}

    /**
     * Lo que le correspondería por todo lo pendiente: bultos pendientes × tarifa por bulto.
     */
    public static function montoSugerido(Trabajador $trabajador): float
    {
        return $trabajador->bultosPorPagar() * (float) $trabajador->empresa->tarifa_por_bulto;
    }

    /**
     * @param  list<int>|null  $produccionIds  días a pagar; null para pagar todo lo pendiente
     */
    public function __invoke(
        Trabajador $trabajador,
        CarbonInterface $fecha,
        ?array $produccionIds = null,
        ?User $user = null,
    ): PagoTrabajador {
        $tarifa = (float) $trabajador->empresa->tarifa_por_bulto;

        if ($tarifa <= 0) {
            throw new ReglaDeNegocio('La tarifa por bulto está en $0. Configúrala en "Datos de la bloquería" antes de pagar.');
        }

        if ($produccionIds === []) {
            throw new ReglaDeNegocio('Elige al menos un día de producción para pagar.');
        }

        return DB::transaction(function () use ($trabajador, $fecha, $produccionIds, $user, $tarifa): PagoTrabajador {
            $pendientes = $trabajador->producciones()->sinPagar()
                ->when($produccionIds !== null, fn ($query) => $query->whereKey($produccionIds))
                ->lockForUpdate()
                ->get();

            if ($pendientes->isEmpty()) {
                throw new ReglaDeNegocio("{$trabajador->nombre} no tiene producción pendiente por pagar.");
            }

            if ($produccionIds !== null && $pendientes->count() !== count(array_unique($produccionIds))) {
                throw new ReglaDeNegocio('Alguno de los días elegidos ya estaba pagado o no es de este trabajador.');
            }

            $bultos = (int) $pendientes->sum('bultos_usados');
            $monto = $bultos * $tarifa;

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
