<?php

namespace App\Actions\Arranque;

use App\Actions\Caja\RegistrarMovimiento;
use App\Actions\Inventario\RegistrarMovimientoInventario;
use App\Enums\ConceptoSistema;
use App\Enums\ItemInventario;
use App\Enums\TipoMovimientoInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\CargaInicial;
use App\Models\Empresa;
use App\Models\MovimientoCaja;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Pasa al sistema lo que dice el papel: el total de la caja y, por sucursal, los bloques,
 * el cemento contado y la arena que quedaba. Todo entra como movimientos de "apertura".
 *
 * Se puede guardar varias veces mientras se compara con el papel: cada vez reemplaza la anterior.
 */
class GuardarCargaInicial
{
    public function __construct(
        private RegistrarMovimientoInventario $moverInventario,
        private RegistrarMovimiento $moverCaja,
    ) {}

    /**
     * @param  array<int, array{bloques: int, cemento: int, arena_bloques: int}>  $sucursales  por id de sucursal
     */
    public function __invoke(
        Empresa $empresa,
        CarbonInterface $fechaCorte,
        string|float $saldoCaja,
        array $sucursales,
        ?User $user = null,
    ): CargaInicial {
        if ((float) $saldoCaja < 0) {
            throw new ReglaDeNegocio('El saldo de caja del papel no puede ser negativo.');
        }

        foreach ($sucursales as $conteo) {
            if (min($conteo['bloques'], $conteo['cemento'], $conteo['arena_bloques']) < 0) {
                throw new ReglaDeNegocio('Los conteos no pueden ser negativos.');
            }
        }

        $registradas = $empresa->sucursales()->whereKey(array_keys($sucursales))->get()->keyBy('id');

        if ($registradas->count() !== count($sucursales)) {
            throw new ReglaDeNegocio('Una de las sucursales no pertenece a la empresa.');
        }

        return DB::transaction(function () use ($empresa, $fechaCorte, $saldoCaja, $sucursales, $user, $registradas): CargaInicial {
            Empresa::query()->whereKey($empresa->getKey())->lockForUpdate()->first();

            $carga = $empresa->cargaInicial()->first();

            if ($carga?->estaCerrada()) {
                throw new ReglaDeNegocio('La carga inicial ya se cerró y no se puede cambiar.');
            }

            if ($carga !== null) {
                $this->deshacer($carga);
                $carga->update(['fecha_corte' => $fechaCorte, 'saldo_caja' => $saldoCaja, 'user_id' => $user?->getKey()]);
            } else {
                $carga = $empresa->cargaInicial()->create(['fecha_corte' => $fechaCorte, 'saldo_caja' => $saldoCaja, 'user_id' => $user?->getKey()]);
            }

            if ((float) $saldoCaja > 0) {
                ($this->moverCaja)($empresa->conceptoSistema(ConceptoSistema::SaldoInicial), $saldoCaja, $fechaCorte, $user, nota: 'Carga inicial desde el papel', origen: $carga);
            }

            $bloque = $empresa->productos()->orderBy('id')->firstOrFail();

            foreach ($sucursales as $sucursalId => $conteo) {
                /** @var Sucursal $sucursal */
                $sucursal = $registradas[$sucursalId];
                $detalle = $carga->detalles()->create(['sucursal_id' => $sucursalId, ...$conteo]);

                if ($conteo['bloques'] > 0) {
                    ($this->moverInventario)($sucursal, ItemInventario::Bloque, $conteo['bloques'], TipoMovimientoInventario::Apertura, $fechaCorte, $bloque, $user, 'Carga inicial', $detalle);
                }

                if ($conteo['cemento'] > 0) {
                    ($this->moverInventario)($sucursal, ItemInventario::Cemento, $conteo['cemento'], TipoMovimientoInventario::Apertura, $fechaCorte, user: $user, nota: 'Carga inicial', origen: $detalle);
                }
            }

            $this->verificarQueNadaQuedeNegativo($empresa);

            return $carga->load('detalles');
        });
    }

    private function deshacer(CargaInicial $carga): void
    {
        MovimientoCaja::query()->whereMorphedTo('origen', $carga)->delete();

        MovimientoInventario::query()
            ->where('origen_type', $carga->detalles()->getRelated()->getMorphClass())
            ->whereIn('origen_id', $carga->detalles()->pluck('id'))
            ->delete();

        $carga->detalles()->delete();
    }

    /**
     * Si ya se vendió o produjo después de la primera carga, bajar un conteo podría dejar existencias negativas.
     */
    private function verificarQueNadaQuedeNegativo(Empresa $empresa): void
    {
        foreach ($empresa->sucursales as $sucursal) {
            foreach (ItemInventario::cases() as $item) {
                if ($sucursal->stock($item) < 0) {
                    throw new ReglaDeNegocio(sprintf(
                        'Con estos conteos, %s quedaría con %d %s en %s, porque ya se registraron movimientos después. Revisa el conteo.',
                        $sucursal->nombre,
                        $sucursal->stock($item),
                        $item->unidad(),
                        mb_strtolower($item->getLabel()),
                    ));
                }
            }
        }
    }
}
