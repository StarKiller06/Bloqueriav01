<?php

namespace Tests\Feature\Actions\Ventas;

use App\Actions\Ventas\RegistrarAbono;
use App\Actions\Ventas\RegistrarVenta;
use App\Enums\ConceptoSistema;
use App\Enums\EstadoVenta;
use App\Enums\FormaPago;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Venta;

class RegistrarAbonoTest extends VentasTestCase
{
    public function test_each_abono_adds_to_the_caja_and_lowers_the_saldo_until_paid(): void
    {
        $venta = $this->ventaACredito(100);
        $abonar = app(RegistrarAbono::class);

        $abonar($venta, 100000, now());
        $this->assertSame('80000.00', $venta->fresh()->saldo_pendiente);
        $this->assertSame(EstadoVenta::Pendiente, $venta->fresh()->estado());

        $abonar($venta, 80000, now());
        $this->assertSame(EstadoVenta::Pagada, $venta->fresh()->estado());
        $this->assertSame(180000.0, $this->empresa->saldoCaja());
        $this->assertSame(2, $this->empresa->movimientosCaja()
            ->where('concepto_caja_id', $this->empresa->conceptoSistema(ConceptoSistema::AbonoCredito)->id)
            ->count());
    }

    public function test_rejects_an_abono_larger_than_the_saldo(): void
    {
        $venta = $this->ventaACredito(100);

        try {
            app(RegistrarAbono::class)($venta, 180001, now());
            $this->fail('Debió rechazar el abono.');
        } catch (ReglaDeNegocio $e) {
            $this->assertStringContainsString('180.000', $e->getMessage());
        }

        $this->assertSame(0.0, $this->empresa->saldoCaja());
    }

    public function test_rejects_an_abono_to_a_cash_sale(): void
    {
        $venta = app(RegistrarVenta::class)($this->sucursal, $this->lineas(10), FormaPago::Contado, now());

        $this->expectException(ReglaDeNegocio::class);

        app(RegistrarAbono::class)($venta, 1000, now());
    }

    private function ventaACredito(int $bloques): Venta
    {
        return app(RegistrarVenta::class)($this->sucursal, $this->lineas($bloques), FormaPago::Credito, now(), $this->cliente);
    }
}
