<?php

namespace App\Actions\Caja;

use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Única puerta de entrada para mover dinero en la caja.
 * Los módulos de ventas, compras y pagos la usarán pasando su registro como origen.
 */
class RegistrarMovimiento
{
    public function __invoke(
        ConceptoCaja $concepto,
        string|float $monto,
        CarbonInterface $fecha,
        ?User $user = null,
        ?Sucursal $sucursal = null,
        ?string $nota = null,
        ?Model $origen = null,
    ): MovimientoCaja {
        if ((float) $monto <= 0) {
            throw new InvalidArgumentException('El monto debe ser mayor que cero.');
        }

        if ($sucursal !== null && $sucursal->empresa_id !== $concepto->empresa_id) {
            throw new InvalidArgumentException('La sucursal no pertenece a la empresa del concepto.');
        }

        $movimiento = new MovimientoCaja([
            'sucursal_id' => $sucursal?->getKey(),
            'concepto_caja_id' => $concepto->getKey(),
            'user_id' => $user?->getKey(),
            'tipo' => $concepto->tipo,
            'monto' => $monto,
            'fecha' => $fecha,
            'nota' => $nota,
        ]);

        $movimiento->empresa()->associate($concepto->empresa_id);

        if ($origen !== null) {
            $movimiento->origen()->associate($origen);
        }

        $movimiento->save();

        return $movimiento;
    }
}
