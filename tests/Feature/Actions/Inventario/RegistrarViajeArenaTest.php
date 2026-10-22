<?php

namespace Tests\Feature\Actions\Inventario;

use App\Actions\Inventario\RegistrarViajeArena;
use App\Models\Empresa;
use App\Models\Produccion;
use App\Models\Sucursal;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarViajeArenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_standard_viaje_adds_the_empresas_bloques_per_viaje_and_charges_the_caja(): void
    {
        $empresa = Empresa::factory()->create(['bloques_por_viaje' => 1200]);
        $sucursal = Sucursal::factory()->for($empresa)->create();

        $viaje = app(RegistrarViajeArena::class)($sucursal, now(), 700000);

        $this->assertSame(1200, $viaje->bloques_estimados);
        $this->assertSame(1200, $sucursal->arenaRestante());
        $this->assertSame(-700000.0, $empresa->saldoCaja());
        $this->assertTrue($empresa->movimientosCaja()->sole()->sucursal->is($sucursal));
    }

    public function test_a_smaller_viaje_estimates_proportionally(): void
    {
        $empresa = Empresa::factory()->create(['bloques_por_viaje' => 1200]);
        $sucursal = Sucursal::factory()->for($empresa)->create();

        $viaje = app(RegistrarViajeArena::class)($sucursal, now(), 350000, 3.5);

        $this->assertSame(600, $viaje->bloques_estimados);
    }

    public function test_production_consumes_the_arena_counter(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        app(RegistrarViajeArena::class)($sucursal, now(), 700000);
        Produccion::factory()->for(Trabajador::factory()->for($sucursal))->resultado(4, 412)->create();

        $this->assertSame(788, $sucursal->arenaRestante());
    }
}
