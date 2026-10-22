<?php

namespace App\Actions\Reportes;

use App\Exceptions\ReglaDeNegocio;
use App\Models\CierreMensual;

/**
 * Deshace el cierre del último mes cerrado, por si hubo que corregir algo.
 * Solo el último: reabrir uno de en medio dejaría reportes posteriores desactualizados.
 */
class ReabrirMes
{
    public function __invoke(CierreMensual $cierre): void
    {
        $ultimo = $cierre->empresa->cierresMensuales()->orderByDesc('anio')->orderByDesc('mes')->first();

        if (! $cierre->is($ultimo)) {
            throw new ReglaDeNegocio('Solo se puede reabrir el último mes cerrado.');
        }

        $cierre->delete();
    }
}
