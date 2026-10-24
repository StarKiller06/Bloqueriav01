<?php

namespace Tests\Feature\Actions\Produccion;

use App\Actions\Produccion\PagarTrabajador;
use App\Actions\Produccion\RegistrarProduccion;
use App\Enums\ConceptoSistema;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Produccion;
use App\Models\Sucursal;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagarTrabajadorTest extends TestCase
{
    use RefreshDatabase;

    public function test_pays_per_bulto_ignoring_extra_bloques(): void
    {
        $trabajador = $this->trabajador(tarifa: 15000);
        Produccion::factory()->for($trabajador)->resultado(4, 412)->create();
        Produccion::factory()->for($trabajador)->resultado(3, 284)->create();

        $this->assertSame(105000.0, PagarTrabajador::montoSugerido($trabajador));

        $pago = app(PagarTrabajador::class)($trabajador, now());

        $this->assertSame(7, $pago->bultos_pagados);
        $this->assertSame('105000.00', $pago->monto);
        $this->assertSame(0, $trabajador->bultosPorPagar());
        $this->assertSame(-105000.0, $trabajador->empresa->saldoCaja());
        $this->assertTrue($trabajador->empresa->movimientosCaja()->sole()->concepto->is(
            $trabajador->empresa->conceptoSistema(ConceptoSistema::PagoTrabajador),
        ));
    }

    public function test_pays_only_the_chosen_days_and_the_amount_matches_their_bultos(): void
    {
        $trabajador = $this->trabajador(tarifa: 15000);
        $lunes = Produccion::factory()->for($trabajador)->resultado(4, 412)->create();
        $martes = Produccion::factory()->for($trabajador)->resultado(3, 284)->create();

        $pago = app(PagarTrabajador::class)($trabajador, now(), [$martes->id]);

        $this->assertSame(3, $pago->bultos_pagados);
        $this->assertSame('45000.00', $pago->monto);
        $this->assertTrue($martes->fresh()->estaPagada());
        $this->assertFalse($lunes->fresh()->estaPagada());
        $this->assertSame(4, $trabajador->bultosPorPagar());
    }

    public function test_rejects_days_of_another_trabajador(): void
    {
        $trabajador = $this->trabajador(tarifa: 15000);
        Produccion::factory()->for($trabajador)->resultado(4, 412)->create();
        $ajena = Produccion::factory()->create();

        $this->expectException(ReglaDeNegocio::class);

        app(PagarTrabajador::class)($trabajador, now(), [$ajena->id]);
    }

    public function test_paying_does_not_touch_the_cemento(): void
    {
        $trabajador = $this->trabajador(tarifa: 15000);
        MovimientoInventario::factory()->for($trabajador->sucursal)->cemento(10)->create();
        app(RegistrarProduccion::class)($trabajador, $trabajador->empresa->productos()->sole(), 4, 412, now());

        app(PagarTrabajador::class)($trabajador, now());

        $this->assertSame(6, $trabajador->sucursal->stock(ItemInventario::Cemento));
    }

    public function test_does_not_pay_the_same_produccion_twice(): void
    {
        $trabajador = $this->trabajador(tarifa: 15000);
        Produccion::factory()->for($trabajador)->resultado(4, 412)->create();
        app(PagarTrabajador::class)($trabajador, now());

        $this->expectException(ReglaDeNegocio::class);

        app(PagarTrabajador::class)($trabajador, now());
    }

    public function test_rejects_paying_when_the_tarifa_is_zero(): void
    {
        $trabajador = $this->trabajador(tarifa: 0);
        Produccion::factory()->for($trabajador)->resultado(4, 412)->create();

        $this->expectException(ReglaDeNegocio::class);

        app(PagarTrabajador::class)($trabajador, now());
    }

    private function trabajador(int $tarifa): Trabajador
    {
        $empresa = Empresa::factory()->create(['tarifa_por_bulto' => $tarifa]);

        return Trabajador::factory()->for(Sucursal::factory()->for($empresa))->create();
    }
}
