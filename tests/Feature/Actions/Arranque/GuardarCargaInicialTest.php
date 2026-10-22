<?php

namespace Tests\Feature\Actions\Arranque;

use App\Actions\Arranque\CerrarCargaInicial;
use App\Actions\Arranque\GuardarCargaInicial;
use App\Actions\Ventas\RegistrarVenta;
use App\Enums\FormaPago;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Empresa;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardarCargaInicialTest extends TestCase
{
    use RefreshDatabase;

    public function test_brings_the_paper_into_the_caja_and_the_inventory(): void
    {
        $empresa = Empresa::factory()->create();
        [$norte, $sur] = Sucursal::factory()->count(2)->for($empresa)->create();

        app(GuardarCargaInicial::class)($empresa, now()->startOfMonth(), 2500000, [
            $norte->id => ['bloques' => 1200, 'cemento' => 8, 'arena_bloques' => 400],
            $sur->id => ['bloques' => 300, 'cemento' => 0, 'arena_bloques' => 0],
        ]);

        $this->assertSame(2500000.0, $empresa->saldoCaja());
        $this->assertSame(1200, $norte->stock(ItemInventario::Bloque));
        $this->assertSame(8, $norte->stock(ItemInventario::Cemento));
        $this->assertSame(400, $norte->arenaRestante());
        $this->assertSame(1500, $empresa->stockGeneral(ItemInventario::Bloque));
    }

    public function test_saving_again_replaces_the_previous_load_instead_of_adding_to_it(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        $guardar = app(GuardarCargaInicial::class);

        $guardar($empresa, now()->startOfMonth(), 2500000, [$sucursal->id => ['bloques' => 1200, 'cemento' => 8, 'arena_bloques' => 400]]);
        $guardar($empresa, now()->startOfMonth(), 2400000, [$sucursal->id => ['bloques' => 1150, 'cemento' => 8, 'arena_bloques' => 400]]);

        $this->assertSame(2400000.0, $empresa->saldoCaja());
        $this->assertSame(1150, $sucursal->stock(ItemInventario::Bloque));
        $this->assertSame(1, $empresa->cargaInicial()->count());
    }

    public function test_a_closed_load_can_no_longer_change(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        $carga = app(GuardarCargaInicial::class)($empresa, now()->startOfMonth(), 100, [$sucursal->id => ['bloques' => 10, 'cemento' => 0, 'arena_bloques' => 0]]);
        app(CerrarCargaInicial::class)($carga);

        $this->expectException(ReglaDeNegocio::class);

        app(GuardarCargaInicial::class)($empresa, now()->startOfMonth(), 200, [$sucursal->id => ['bloques' => 10, 'cemento' => 0, 'arena_bloques' => 0]]);
    }

    public function test_rejects_lowering_a_count_below_what_was_already_sold(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        $guardar = app(GuardarCargaInicial::class);
        $guardar($empresa, now()->startOfMonth(), 0, [$sucursal->id => ['bloques' => 500, 'cemento' => 0, 'arena_bloques' => 0]]);
        app(RegistrarVenta::class)($sucursal, [['producto' => $empresa->productos()->sole(), 'cantidad' => 400, 'precio' => 1800]], FormaPago::Contado, now());

        try {
            $guardar($empresa, now()->startOfMonth(), 0, [$sucursal->id => ['bloques' => 300, 'cemento' => 0, 'arena_bloques' => 0]]);
            $this->fail('Debió rechazar el conteo.');
        } catch (ReglaDeNegocio $e) {
            $this->assertStringContainsString('-100', $e->getMessage());
        }

        $this->assertSame(100, $sucursal->stock(ItemInventario::Bloque));
    }

    public function test_rejects_a_sucursal_of_another_empresa(): void
    {
        $empresa = Empresa::factory()->create();

        $this->expectException(ReglaDeNegocio::class);

        app(GuardarCargaInicial::class)($empresa, now(), 0, [Sucursal::factory()->create()->id => ['bloques' => 1, 'cemento' => 0, 'arena_bloques' => 0]]);
    }
}
