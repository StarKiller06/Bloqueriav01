<?php

namespace App\Actions\Inventario;

use App\Models\Sucursal;

/**
 * Bloquea las filas de las sucursales hasta que termine la transacción. Así, si dos encargados
 * registran producción al mismo tiempo, el segundo espera y ve el cemento que dejó el primero.
 */
trait BloqueaSucursales
{
    protected function bloquear(Sucursal ...$sucursales): void
    {
        Sucursal::query()
            ->whereKey(collect($sucursales)->map->getKey()->sort()->values())
            ->lockForUpdate()
            ->get();
    }
}
