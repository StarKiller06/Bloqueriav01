<?php

namespace Tests\Feature\Actions\Ventas;

use App\Actions\Ventas\RegistrarVenta;
use App\Enums\EstadoVenta;
use App\Enums\FormaPago;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Cliente;

class RegistrarVentaTest extends VentasTestCase
{
    public function test_a_cash_sale_subtracts_bloques_and_adds_the_total_to_the_caja(): void
    {
        $venta = app(RegistrarVenta::class)($this->sucursal, $this->lineas(300), FormaPago::Contado, now());

        $this->assertSame('540000.00', $venta->total);
        $this->assertSame(EstadoVenta::Pagada, $venta->estado());
        $this->assertSame(700, $this->sucursal->stock(ItemInventario::Bloque));
        $this->assertSame(540000.0, $this->empresa->saldoCaja());
        $this->assertSame('Consumidor final', $venta->nombreCliente());
    }

    public function test_a_credit_sale_subtracts_bloques_but_leaves_the_caja_untouched(): void
    {
        $venta = app(RegistrarVenta::class)($this->sucursal, $this->lineas(100), FormaPago::Credito, now(), $this->cliente, now()->addDays(30));

        $this->assertSame('180000.00', $venta->saldo_pendiente);
        $this->assertSame(EstadoVenta::Pendiente, $venta->estado());
        $this->assertSame(900, $this->sucursal->stock(ItemInventario::Bloque));
        $this->assertSame(0.0, $this->empresa->saldoCaja());
        $this->assertSame(180000.0, $this->cliente->saldoPendiente());
    }

    public function test_keeps_the_price_used_even_if_the_vigente_changes_later(): void
    {
        $venta = app(RegistrarVenta::class)($this->sucursal, $this->lineas(100, 1700), FormaPago::Contado, now());

        $this->bloque->cambiarPrecio(2000);

        $this->assertSame('1700.00', $venta->detalles()->sole()->precio_unitario);
        $this->assertSame('170000.00', $venta->fresh()->total);
    }

    public function test_numbers_the_sales_of_each_empresa_in_sequence(): void
    {
        $registrar = app(RegistrarVenta::class);

        $primera = $registrar($this->sucursal, $this->lineas(10), FormaPago::Contado, now());
        $segunda = $registrar($this->sucursal, $this->lineas(10), FormaPago::Contado, now());

        $this->assertSame(1, $primera->numero);
        $this->assertSame(2, $segunda->numero);
        $this->assertSame('00002', $segunda->numeroFormateado());
    }

    public function test_rejects_selling_more_bloques_than_the_sucursal_has(): void
    {
        $this->expectException(ReglaDeNegocio::class);

        try {
            app(RegistrarVenta::class)($this->sucursal, $this->lineas(1001), FormaPago::Contado, now());
        } finally {
            $this->assertSame(0, $this->empresa->ventas()->count());
            $this->assertSame(0.0, $this->empresa->saldoCaja());
        }
    }

    public function test_a_credit_sale_requires_a_cliente(): void
    {
        $this->expectException(ReglaDeNegocio::class);

        app(RegistrarVenta::class)($this->sucursal, $this->lineas(10), FormaPago::Credito, now());
    }

    public function test_rejects_a_cliente_of_another_empresa(): void
    {
        $this->expectException(ReglaDeNegocio::class);

        app(RegistrarVenta::class)($this->sucursal, $this->lineas(10), FormaPago::Credito, now(), Cliente::factory()->create());
    }

    public function test_rejects_a_due_date_before_the_sale(): void
    {
        $this->expectException(ReglaDeNegocio::class);

        app(RegistrarVenta::class)($this->sucursal, $this->lineas(10), FormaPago::Credito, now(), $this->cliente, now()->subDay());
    }

    public function test_a_credit_past_its_due_date_is_vencida(): void
    {
        $venta = app(RegistrarVenta::class)($this->sucursal, $this->lineas(10), FormaPago::Credito, now()->subDays(40), $this->cliente, now()->subDays(10));

        $this->assertSame(EstadoVenta::Vencida, $venta->estado());
    }
}
