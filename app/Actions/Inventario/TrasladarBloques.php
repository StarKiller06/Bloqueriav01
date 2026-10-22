<?php

namespace App\Actions\Inventario;

use App\Enums\ItemInventario;
use App\Enums\TipoMovimientoInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\TrasladoBloques;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Mueve bloques de una sucursal a otra. El inventario general no cambia.
 */
class TrasladarBloques
{
    use BloqueaSucursales;

    public function __construct(private RegistrarMovimientoInventario $moverInventario) {}

    public function __invoke(
        Sucursal $origen,
        Sucursal $destino,
        Producto $producto,
        int $cantidad,
        CarbonInterface $fecha,
        ?User $user = null,
        ?string $nota = null,
    ): TrasladoBloques {
        if ($origen->is($destino)) {
            throw new ReglaDeNegocio('La sucursal de origen y la de destino deben ser distintas.');
        }

        if ($origen->empresa_id !== $destino->empresa_id || $producto->empresa_id !== $origen->empresa_id) {
            throw new ReglaDeNegocio('Las sucursales y el producto deben ser de la misma empresa.');
        }

        if ($cantidad <= 0) {
            throw new ReglaDeNegocio('La cantidad a trasladar debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($origen, $destino, $producto, $cantidad, $fecha, $user, $nota): TrasladoBloques {
            $this->bloquear($origen, $destino);

            $traslado = new TrasladoBloques([
                'producto_id' => $producto->getKey(),
                'sucursal_origen_id' => $origen->getKey(),
                'sucursal_destino_id' => $destino->getKey(),
                'cantidad' => $cantidad,
                'fecha' => $fecha,
                'nota' => $nota,
                'user_id' => $user?->getKey(),
            ]);
            $traslado->empresa()->associate($origen->empresa_id);
            $traslado->save();

            ($this->moverInventario)($origen, ItemInventario::Bloque, -$cantidad, TipoMovimientoInventario::Traslado, $fecha, $producto, $user, "Hacia {$destino->nombre}", $traslado);
            ($this->moverInventario)($destino, ItemInventario::Bloque, $cantidad, TipoMovimientoInventario::Traslado, $fecha, $producto, $user, "Desde {$origen->nombre}", $traslado);

            return $traslado;
        });
    }
}
