<?php

namespace App\Actions\Inventario;

use App\Actions\Caja\RegistrarMovimiento;
use App\Enums\ConceptoSistema;
use App\Enums\ItemInventario;
use App\Enums\TipoMovimientoInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\CompraCemento;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * El cemento llega a la empresa, se paga de la caja única y se reparte entre sucursales.
 */
class RegistrarCompraCemento
{
    public function __construct(
        private RegistrarMovimientoInventario $moverInventario,
        private RegistrarMovimiento $moverCaja,
    ) {}

    /**
     * @param  array<int, int>  $reparto  bultos por id de sucursal
     */
    public function __invoke(
        Empresa $empresa,
        CarbonInterface $fecha,
        int $bultos,
        string|float $precioBulto,
        array $reparto,
        ?string $proveedor = null,
        ?User $user = null,
    ): CompraCemento {
        $reparto = array_filter($reparto, fn (int $cantidad): bool => $cantidad > 0);

        if ($bultos <= 0) {
            throw new ReglaDeNegocio('La compra debe tener al menos un bulto.');
        }

        if ((float) $precioBulto <= 0) {
            throw new ReglaDeNegocio('El precio por bulto debe ser mayor que cero.');
        }

        if (array_sum($reparto) !== $bultos) {
            throw new ReglaDeNegocio(sprintf(
                'El reparto suma %d bultos y la compra es de %d. Deben coincidir.',
                array_sum($reparto),
                $bultos,
            ));
        }

        $sucursales = $empresa->sucursales()->whereKey(array_keys($reparto))->get()->keyBy('id');

        if ($sucursales->count() !== count($reparto)) {
            throw new ReglaDeNegocio('Una de las sucursales del reparto no pertenece a la empresa.');
        }

        return DB::transaction(function () use ($empresa, $fecha, $bultos, $precioBulto, $reparto, $proveedor, $user, $sucursales): CompraCemento {
            $compra = new CompraCemento([
                'fecha' => $fecha,
                'proveedor' => $proveedor,
                'bultos' => $bultos,
                'precio_bulto' => $precioBulto,
                'user_id' => $user?->getKey(),
            ]);
            $compra->empresa()->associate($empresa);
            $compra->save();

            foreach ($reparto as $sucursalId => $cantidad) {
                /** @var Sucursal $sucursal */
                $sucursal = $sucursales[$sucursalId];

                $repartoCemento = $compra->repartos()->create(['sucursal_id' => $sucursalId, 'bultos' => $cantidad]);

                ($this->moverInventario)(
                    $sucursal, ItemInventario::Cemento, $cantidad, TipoMovimientoInventario::RepartoCemento,
                    $fecha, user: $user, origen: $repartoCemento,
                );
            }

            ($this->moverCaja)(
                $empresa->conceptoSistema(ConceptoSistema::CompraCemento), $compra->total(), $fecha,
                user: $user, nota: $proveedor ? "Proveedor: {$proveedor}" : null, origen: $compra,
            );

            return $compra;
        });
    }
}
