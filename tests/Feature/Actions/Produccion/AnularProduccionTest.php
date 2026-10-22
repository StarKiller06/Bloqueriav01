<?php

namespace Tests\Feature\Actions\Produccion;

use App\Actions\Inventario\TrasladarBloques;
use App\Actions\Produccion\AnularProduccion;
use App\Actions\Produccion\PagarTrabajador;
use App\Actions\Produccion\RegistrarProduccion;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnularProduccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_cemento_and_removes_the_bloques(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        MovimientoInventario::factory()->for($sucursal)->cemento(10)->create();
        $produccion = app(RegistrarProduccion::class)(Trabajador::factory()->for($sucursal)->create(), $empresa->productos()->sole(), 4, 412, now());

        app(AnularProduccion::class)($produccion);

        $this->assertModelMissing($produccion);
        $this->assertSame(10, $sucursal->stock(ItemInventario::Cemento));
        $this->assertSame(0, $sucursal->stock(ItemInventario::Bloque));
    }

    public function test_rejects_a_paid_produccion(): void
    {
        $empresa = Empresa::factory()->create(['tarifa_por_bulto' => 15000]);
        $sucursal = Sucursal::factory()->for($empresa)->create();
        MovimientoInventario::factory()->for($sucursal)->cemento(10)->create();
        $trabajador = Trabajador::factory()->for($sucursal)->create();
        $produccion = app(RegistrarProduccion::class)($trabajador, $empresa->productos()->sole(), 4, 412, now());
        app(PagarTrabajador::class)($trabajador, now());

        $this->expectException(ReglaDeNegocio::class);

        app(AnularProduccion::class)($produccion->fresh());
    }

    public function test_rejects_when_its_bloques_already_left_the_sucursal(): void
    {
        $empresa = Empresa::factory()->create();
        [$norte, $sur] = Sucursal::factory()->count(2)->for($empresa)->create();
        MovimientoInventario::factory()->for($norte)->cemento(10)->create();
        $bloque = $empresa->productos()->sole();
        $produccion = app(RegistrarProduccion::class)(Trabajador::factory()->for($norte)->create(), $bloque, 4, 412, now());
        app(TrasladarBloques::class)($norte, $sur, $bloque, 300, now());

        $this->expectException(ReglaDeNegocio::class);

        app(AnularProduccion::class)($produccion);
    }
}
