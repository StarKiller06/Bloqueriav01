<?php

namespace App\Actions\Produccion;

use App\Actions\Inventario\BloqueaSucursales;
use App\Actions\Inventario\RegistrarMovimientoInventario;
use App\Enums\ItemInventario;
use App\Enums\TipoMovimientoInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Produccion;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Lo que sacó un trabajador: gasta bultos de cemento de su sucursal y suma bloques.
 * No toca la caja: el dinero entra solo cuando se vende.
 */
class RegistrarProduccion
{
    use BloqueaSucursales;

    public function __construct(private RegistrarMovimientoInventario $moverInventario) {}

    public function __invoke(
        Trabajador $trabajador,
        Producto $producto,
        int $bultosUsados,
        int $bloquesSacados,
        CarbonInterface $fecha,
        ?User $user = null,
    ): Produccion {
        if (! $trabajador->activo) {
            throw new ReglaDeNegocio("{$trabajador->nombre} está inactivo. Actívalo antes de registrarle producción.");
        }

        if ($producto->empresa_id !== $trabajador->empresa_id) {
            throw new ReglaDeNegocio('El producto no pertenece a la empresa del trabajador.');
        }

        if ($bultosUsados <= 0) {
            throw new ReglaDeNegocio('Debe usar al menos un bulto de cemento.');
        }

        if ($bloquesSacados <= 0) {
            throw new ReglaDeNegocio('Debe sacar al menos un bloque.');
        }

        $sucursal = $trabajador->sucursal;
        $esperados = $bultosUsados * $trabajador->empresa->bloques_por_bulto;

        return DB::transaction(function () use ($trabajador, $producto, $bultosUsados, $bloquesSacados, $fecha, $user, $sucursal, $esperados): Produccion {
            $this->bloquear($sucursal);

            $produccion = new Produccion([
                'sucursal_id' => $sucursal->getKey(),
                'trabajador_id' => $trabajador->getKey(),
                'producto_id' => $producto->getKey(),
                'fecha' => $fecha,
                'bultos_usados' => $bultosUsados,
                'bloques_sacados' => $bloquesSacados,
                'bloques_esperados' => $esperados,
                'diferencia' => $bloquesSacados - $esperados,
                'user_id' => $user?->getKey(),
            ]);
            $produccion->empresa()->associate($trabajador->empresa_id);
            $produccion->save();

            ($this->moverInventario)($sucursal, ItemInventario::Cemento, -$bultosUsados, TipoMovimientoInventario::ConsumoCemento, $fecha, user: $user, nota: $trabajador->nombre, origen: $produccion);
            ($this->moverInventario)($sucursal, ItemInventario::Bloque, $bloquesSacados, TipoMovimientoInventario::Produccion, $fecha, $producto, $user, $trabajador->nombre, $produccion);

            return $produccion;
        });
    }
}
