<?php

namespace App\Actions\Produccion;

use App\Actions\Inventario\BloqueaSucursales;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Produccion;
use Illuminate\Support\Facades\DB;

/**
 * Borra una producción mal registrada y deshace su efecto en el inventario
 * (devuelve el cemento y retira los bloques). Solo si aún no se ha pagado.
 */
class AnularProduccion
{
    use BloqueaSucursales;

    public function __invoke(Produccion $produccion): void
    {
        if ($produccion->estaPagada()) {
            throw new ReglaDeNegocio('Esta producción ya se pagó y no se puede anular.');
        }

        if ($produccion->empresa->mesCerrado($produccion->fecha)) {
            throw ReglaDeNegocio::mesCerrado($produccion->fecha);
        }

        DB::transaction(function () use ($produccion): void {
            $sucursal = $produccion->sucursal;
            $this->bloquear($sucursal);

            if ($sucursal->stock(ItemInventario::Bloque) < $produccion->bloques_sacados) {
                throw new ReglaDeNegocio(sprintf(
                    'No se puede anular: en %s quedan %d bloques y la producción sumó %d. Parte ya se vendió o trasladó.',
                    $sucursal->nombre,
                    $sucursal->stock(ItemInventario::Bloque),
                    $produccion->bloques_sacados,
                ));
            }

            $produccion->movimientosInventario()->delete();
            $produccion->delete();
        });
    }
}
