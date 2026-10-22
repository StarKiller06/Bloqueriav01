<?php

namespace App\Actions\Ventas;

use App\Actions\Caja\RegistrarMovimiento;
use App\Enums\ConceptoSistema;
use App\Enums\FormaPago;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Abono;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

/**
 * Un pago parcial o total de una venta a crédito: entra a la caja y baja el saldo.
 */
class RegistrarAbono
{
    public function __construct(private RegistrarMovimiento $moverCaja) {}

    public function __invoke(Venta $venta, string|float $monto, CarbonInterface $fecha, ?User $user = null, ?string $nota = null): Abono
    {
        if ((float) $monto <= 0) {
            throw new ReglaDeNegocio('El abono debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($venta, $monto, $fecha, $user, $nota): Abono {
            $venta = Venta::query()->whereKey($venta->getKey())->lockForUpdate()->firstOrFail();

            if ($venta->estaAnulada()) {
                throw new ReglaDeNegocio('La venta está anulada.');
            }

            if ($venta->forma_pago !== FormaPago::Credito) {
                throw new ReglaDeNegocio('Solo las ventas a crédito reciben abonos.');
            }

            if ((float) $monto > (float) $venta->saldo_pendiente) {
                throw new ReglaDeNegocio(sprintf(
                    'El abono supera lo que se debe: el saldo de la venta %s es %s.',
                    $venta->numeroFormateado(),
                    Number::currency((float) $venta->saldo_pendiente, 'COP', 'es_CO', 0),
                ));
            }

            $abono = new Abono([
                'venta_id' => $venta->getKey(),
                'fecha' => $fecha,
                'monto' => $monto,
                'nota' => $nota,
                'user_id' => $user?->getKey(),
            ]);
            $abono->empresa()->associate($venta->empresa_id);
            $abono->save();

            $venta->update(['saldo_pendiente' => (float) $venta->saldo_pendiente - (float) $monto]);

            ($this->moverCaja)(
                $venta->empresa->conceptoSistema(ConceptoSistema::AbonoCredito), $monto, $fecha,
                $user, $venta->sucursal, "Venta {$venta->numeroFormateado()} · {$venta->nombreCliente()}", $abono,
            );

            return $abono;
        });
    }
}
