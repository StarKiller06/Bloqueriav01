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
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Vende bloques de una sucursal. Siempre resta del inventario.
 * De contado, el total entra a la caja ya; a crédito, queda como saldo del cliente.
 */
class RegistrarVenta
{
    use BloqueaSucursales;

    public function __construct(
        private RegistrarMovimientoInventario $moverInventario,
        private RegistrarMovimiento $moverCaja,
    ) {}

    /**
     * @param  list<array{producto: Producto, cantidad: int, precio: string|float}>  $lineas
     */
    public function __invoke(
        Sucursal $sucursal,
        array $lineas,
        FormaPago $formaPago,
        CarbonInterface $fecha,
        ?Cliente $cliente = null,
        ?CarbonInterface $fechaVencimiento = null,
        ?User $user = null,
    ): Venta {
        $this->validar($sucursal, $lineas, $formaPago, $fecha, $cliente, $fechaVencimiento);

        $total = collect($lineas)->sum(fn (array $linea): float => $linea['cantidad'] * (float) $linea['precio']);

        if ($total <= 0) {
            throw new ReglaDeNegocio('El total de la venta debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($sucursal, $lineas, $formaPago, $fecha, $cliente, $fechaVencimiento, $user, $total): Venta {
            $this->bloquear($sucursal);
            $empresa = Empresa::query()->whereKey($sucursal->empresa_id)->lockForUpdate()->firstOrFail();

            $venta = new Venta([
                'numero' => (int) $empresa->ventas()->max('numero') + 1,
                'sucursal_id' => $sucursal->getKey(),
                'cliente_id' => $cliente?->getKey(),
                'fecha' => $fecha,
                'forma_pago' => $formaPago,
                'fecha_vencimiento' => $formaPago === FormaPago::Credito ? $fechaVencimiento : null,
                'total' => $total,
                'saldo_pendiente' => $formaPago === FormaPago::Credito ? $total : 0,
                'user_id' => $user?->getKey(),
            ]);
            $venta->empresa()->associate($empresa);
            $venta->save();

            foreach ($lineas as $linea) {
                $venta->detalles()->create([
                    'producto_id' => $linea['producto']->getKey(),
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio'],
                    'subtotal' => $linea['cantidad'] * (float) $linea['precio'],
                ]);

                ($this->moverInventario)(
                    $sucursal, ItemInventario::Bloque, -$linea['cantidad'], TipoMovimientoInventario::Venta, $fecha,
                    $linea['producto'], $user, "Venta {$venta->numeroFormateado()}", $venta,
                );
            }

            if ($formaPago === FormaPago::Contado) {
                ($this->moverCaja)(
                    $empresa->conceptoSistema(ConceptoSistema::VentaContado), $total, $fecha,
                    $user, $sucursal, "Venta {$venta->numeroFormateado()} · {$venta->nombreCliente()}", $venta,
                );
            }

            return $venta;
        });
    }

    /**
     * @param  list<array{producto: Producto, cantidad: int, precio: string|float}>  $lineas
     */
    private function validar(Sucursal $sucursal, array $lineas, FormaPago $formaPago, CarbonInterface $fecha, ?Cliente $cliente, ?CarbonInterface $fechaVencimiento): void
    {
        if ($lineas === []) {
            throw new ReglaDeNegocio('Agrega al menos un producto a la venta.');
        }

        foreach ($lineas as $linea) {
            if ($linea['producto']->empresa_id !== $sucursal->empresa_id) {
                throw new ReglaDeNegocio('Uno de los productos no pertenece a la empresa.');
            }

            if ($linea['cantidad'] <= 0) {
                throw new ReglaDeNegocio('Cada producto debe tener una cantidad mayor que cero.');
            }

            if ((float) $linea['precio'] < 0) {
                throw new ReglaDeNegocio('El precio no puede ser negativo.');
            }
        }

        if ($cliente !== null && $cliente->empresa_id !== $sucursal->empresa_id) {
            throw new ReglaDeNegocio('El cliente no pertenece a la empresa.');
        }

        if ($formaPago === FormaPago::Credito && $cliente === null) {
            throw new ReglaDeNegocio('Una venta a crédito necesita un cliente registrado, para saber quién debe.');
        }

        if ($fechaVencimiento !== null && $fechaVencimiento->isBefore($fecha->copy()->startOfDay())) {
            throw new ReglaDeNegocio('La fecha límite de pago no puede ser anterior a la fecha de la venta.');
        }
    }
}
