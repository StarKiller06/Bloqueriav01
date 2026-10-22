<?php

namespace App\Actions\Inventario;

use App\Enums\ItemInventario;
use App\Enums\TipoMovimientoInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Corrige el inventario cuando el conteo físico no coincide con el sistema.
 * Siempre lleva una nota que explique por qué.
 */
class AjustarInventario
{
    use BloqueaSucursales;

    public function __construct(private RegistrarMovimientoInventario $moverInventario) {}

    public function __invoke(
        Sucursal $sucursal,
        ItemInventario $item,
        int $cantidad,
        string $nota,
        CarbonInterface $fecha,
        ?Producto $producto = null,
        ?User $user = null,
    ): MovimientoInventario {
        if (blank($nota)) {
            throw new ReglaDeNegocio('Explica en la nota por qué se ajusta el inventario.');
        }

        if ($item === ItemInventario::Bloque && $producto === null) {
            throw new ReglaDeNegocio('Indica qué producto se ajusta.');
        }

        return DB::transaction(function () use ($sucursal, $item, $cantidad, $nota, $fecha, $producto, $user): MovimientoInventario {
            $this->bloquear($sucursal);

            return ($this->moverInventario)(
                $sucursal, $item, $cantidad, TipoMovimientoInventario::Ajuste, $fecha,
                $item === ItemInventario::Bloque ? $producto : null, $user, $nota,
            );
        });
    }
}
