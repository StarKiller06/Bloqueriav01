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
use Illuminate\Database\Eloquent\Model;

/**
 * Única puerta para mover bloques o cemento. Una salida nunca deja el inventario en negativo.
 *
 * Quien la llame debe hacerlo dentro de una transacción y con la sucursal bloqueada
 * (ver BloqueaSucursales), para que dos personas no gasten el mismo cemento a la vez.
 */
class RegistrarMovimientoInventario
{
    public function __invoke(
        Sucursal $sucursal,
        ItemInventario $item,
        int $cantidad,
        TipoMovimientoInventario $tipo,
        CarbonInterface $fecha,
        ?Producto $producto = null,
        ?User $user = null,
        ?string $nota = null,
        ?Model $origen = null,
    ): MovimientoInventario {
        if ($cantidad === 0) {
            throw new ReglaDeNegocio('La cantidad no puede ser cero.');
        }

        if ($sucursal->empresa->mesCerrado($fecha)) {
            throw ReglaDeNegocio::mesCerrado($fecha);
        }

        if ($cantidad < 0 && $sucursal->stock($item) + $cantidad < 0) {
            throw new ReglaDeNegocio(sprintf(
                'No hay suficiente %s en %s: hay %d %s y se necesitan %d.',
                mb_strtolower($item->getLabel()),
                $sucursal->nombre,
                $sucursal->stock($item),
                $item->unidad(),
                -$cantidad,
            ));
        }

        $movimiento = new MovimientoInventario([
            'sucursal_id' => $sucursal->getKey(),
            'item' => $item,
            'producto_id' => $producto?->getKey(),
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'fecha' => $fecha,
            'user_id' => $user?->getKey(),
            'nota' => $nota,
        ]);

        $movimiento->empresa()->associate($sucursal->empresa_id);

        if ($origen !== null) {
            $movimiento->origen()->associate($origen);
        }

        $movimiento->save();

        return $movimiento;
    }
}
