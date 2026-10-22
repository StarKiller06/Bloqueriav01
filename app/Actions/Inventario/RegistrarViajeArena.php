<?php

namespace App\Actions\Inventario;

use App\Actions\Caja\RegistrarMovimiento;
use App\Enums\ConceptoSistema;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\ViajeArena;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * La arena no es inventario: cada viaje suma bloques estimados al contador de la sucursal
 * y su costo sale de la caja.
 */
class RegistrarViajeArena
{
    /**
     * Metros cúbicos de un viaje estándar, al que corresponde "bloques por viaje" de la empresa.
     */
    public const float METROS_POR_VIAJE = 7.0;

    public function __construct(private RegistrarMovimiento $moverCaja) {}

    public function __invoke(
        Sucursal $sucursal,
        CarbonInterface $fecha,
        string|float $costo,
        string|float $metrosCubicos = self::METROS_POR_VIAJE,
        ?User $user = null,
    ): ViajeArena {
        if ((float) $costo <= 0) {
            throw new ReglaDeNegocio('El costo del viaje debe ser mayor que cero.');
        }

        if ((float) $metrosCubicos <= 0) {
            throw new ReglaDeNegocio('Los metros cúbicos deben ser mayores que cero.');
        }

        $empresa = $sucursal->empresa;

        return DB::transaction(function () use ($sucursal, $empresa, $fecha, $costo, $metrosCubicos, $user): ViajeArena {
            $viaje = new ViajeArena([
                'sucursal_id' => $sucursal->getKey(),
                'fecha' => $fecha,
                'metros_cubicos' => $metrosCubicos,
                'costo' => $costo,
                'bloques_estimados' => (int) round($empresa->bloques_por_viaje * (float) $metrosCubicos / self::METROS_POR_VIAJE),
                'user_id' => $user?->getKey(),
            ]);
            $viaje->empresa()->associate($empresa);
            $viaje->save();

            ($this->moverCaja)(
                $empresa->conceptoSistema(ConceptoSistema::ViajeArena), $costo, $fecha,
                user: $user, sucursal: $sucursal, origen: $viaje,
            );

            return $viaje;
        });
    }
}
