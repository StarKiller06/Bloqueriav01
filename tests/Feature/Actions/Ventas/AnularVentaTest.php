<?php

namespace Tests\Feature\Actions\Ventas;

use App\Actions\Ventas\AnularVenta;
use App\Actions\Ventas\RegistrarAbono;
use App\Actions\Ventas\RegistrarVenta;
use App\Enums\EstadoVenta;
use App\Enums\FormaPago;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;

class AnularVentaTest extends VentasTestCase
{
    public function test_annulling_a_cash_sale_returns_bloques_and_takes_the_money_out_of_the_caja(): void
    {
        $venta = app(RegistrarVenta::class)($this->sucursal, $this->lineas(300), FormaPago::Contado, now());

        app(AnularVenta::class)($venta, 'Se registró en la sucursal equivocada', now());

        $this->assertSame(EstadoVenta::Anulada, $venta->fresh()->estado());
        $this->assertSame(1000, $this->sucursal->stock(ItemInventario::Bloque));
        $this->assertSame(0.0, $this->empresa->saldoCaja());
    }

    public function test_annulling_a_credit_sale_refunds_only_what_was_paid(): void
    {
        $venta = app(RegistrarVenta::class)($this->sucursal, $this->lineas(100), FormaPago::Credito, now(), $this->cliente);
        app(RegistrarAbono::class)($venta, 50000, now());

        app(AnularVenta::class)($venta->fresh(), 'El cliente devolvió los bloques', now());

        $this->assertSame(0.0, $this->empresa->saldoCaja());
        $this->assertSame(0.0, $this->cliente->saldoPendiente());
        $this->assertSame(1000, $this->sucursal->stock(ItemInventario::Bloque));
    }

    public function test_rejects_annulling_twice(): void
    {
        $venta = app(RegistrarVenta::class)($this->sucursal, $this->lineas(10), FormaPago::Contado, now());
        app(AnularVenta::class)($venta, 'Error', now());

        $this->expectException(ReglaDeNegocio::class);

        app(AnularVenta::class)($venta, 'Error', now());
    }

    public function test_requires_a_reason(): void
    {
        $venta = app(RegistrarVenta::class)($this->sucursal, $this->lineas(10), FormaPago::Contado, now());

        $this->expectException(ReglaDeNegocio::class);

        app(AnularVenta::class)($venta, ' ', now());
    }
}
