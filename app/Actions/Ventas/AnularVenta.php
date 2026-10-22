<?php

namespace App\Actions\Ventas;

use App\Actions\Caja\RegistrarMovimiento;
use App\Actions\Inventario\BloqueaSucursales;
use App\Actions\Inventario\RegistrarMovimientoInventario;
use App\Enums\ConceptoSistema;
use App\Enums\FormaPago;
use App\Enums\ItemInventario;
use App\Enums\TipoMovimientoInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\DetalleVenta;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Deshace una venta mal hecha sin borrarla: los bloques vuelven a la sucursal y lo que
 * el cliente pagó (el total si fue de contado, o sus abonos si fue a crédito) sale de la caja
 * con el concepto "Anulación de venta", porque se le devuelve.
 */
class AnularVenta
{
    use BloqueaSucursales;

    public function __construct(
        private RegistrarMovimientoInventario $moverInventario,
        private RegistrarMovimiento $moverCaja,
    ) {}

    public function __invoke(Venta $venta, string $motivo, CarbonInterface $fecha, ?User $user = null): void
    {
        if (blank($motivo)) {
            throw new ReglaDeNegocio('Escribe el motivo de la anulación.');
        }

        DB::transaction(function () use ($venta, $motivo, $fecha, $user): void {
            $venta = Venta::query()->whereKey($venta->getKey())->lockForUpdate()->firstOrFail();

            if ($venta->estaAnulada()) {
                throw new ReglaDeNegocio('La venta ya estaba anulada.');
            }

            $this->bloquear($venta->sucursal);

            $venta->detalles->each(fn (DetalleVenta $detalle) => ($this->moverInventario)(
                $venta->sucursal, ItemInventario::Bloque, $detalle->cantidad, TipoMovimientoInventario::Venta, $fecha,
                $detalle->producto, $user, "Anulación venta {$venta->numeroFormateado()}", $venta,
            ));

            $devuelto = $venta->forma_pago === FormaPago::Contado
                ? (float) $venta->total
                : (float) $venta->abonos()->sum('monto');

            if ($devuelto > 0) {
                ($this->moverCaja)(
                    $venta->empresa->conceptoSistema(ConceptoSistema::AnulacionVenta), $devuelto, $fecha,
                    $user, $venta->sucursal, "Venta {$venta->numeroFormateado()}: {$motivo}", $venta,
                );
            }

            $venta->forceFill([
                'anulada_at' => now(),
                'motivo_anulacion' => $motivo,
                'saldo_pendiente' => 0,
            ])->save();
        });
    }
}
