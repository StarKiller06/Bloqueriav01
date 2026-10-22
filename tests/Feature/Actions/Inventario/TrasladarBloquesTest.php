<?php

namespace Tests\Feature\Actions\Inventario;

use App\Actions\Inventario\TrasladarBloques;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrasladarBloquesTest extends TestCase
{
    use RefreshDatabase;

    public function test_moves_bloques_without_changing_the_general_inventory(): void
    {
        $empresa = Empresa::factory()->create();
        [$norte, $sur] = Sucursal::factory()->count(2)->for($empresa)->create();
        MovimientoInventario::factory()->for($norte)->bloques(500)->create();

        app(TrasladarBloques::class)($norte, $sur, $empresa->productos()->sole(), 200, now());

        $this->assertSame(300, $norte->stock(ItemInventario::Bloque));
        $this->assertSame(200, $sur->stock(ItemInventario::Bloque));
        $this->assertSame(500, $empresa->stockGeneral(ItemInventario::Bloque));
    }

    public function test_rejects_moving_more_bloques_than_the_origin_has(): void
    {
        $empresa = Empresa::factory()->create();
        [$norte, $sur] = Sucursal::factory()->count(2)->for($empresa)->create();
        MovimientoInventario::factory()->for($norte)->bloques(100)->create();

        try {
            app(TrasladarBloques::class)($norte, $sur, $empresa->productos()->sole(), 150, now());
            $this->fail('Debió rechazar el traslado.');
        } catch (ReglaDeNegocio) {
            //
        }

        $this->assertSame(100, $norte->stock(ItemInventario::Bloque));
        $this->assertSame(0, $sur->stock(ItemInventario::Bloque));
        $this->assertSame(0, $empresa->trasladosBloques()->count());
    }

    public function test_rejects_the_same_sucursal_as_origin_and_destination(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();

        $this->expectException(ReglaDeNegocio::class);

        app(TrasladarBloques::class)($sucursal, $sucursal, $empresa->productos()->sole(), 10, now());
    }
}
