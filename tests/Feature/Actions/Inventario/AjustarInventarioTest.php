<?php

namespace Tests\Feature\Actions\Inventario;

use App\Actions\Inventario\AjustarInventario;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AjustarInventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_corrects_the_cemento_after_a_physical_count(): void
    {
        $sucursal = Sucursal::factory()->create();
        MovimientoInventario::factory()->for($sucursal)->cemento(10)->create();

        app(AjustarInventario::class)($sucursal, ItemInventario::Cemento, -2, 'Conteo: había 8', now());

        $this->assertSame(8, $sucursal->stock(ItemInventario::Cemento));
    }

    public function test_rejects_an_adjustment_that_leaves_negative_stock(): void
    {
        $sucursal = Sucursal::factory()->create();

        $this->expectException(ReglaDeNegocio::class);

        app(AjustarInventario::class)($sucursal, ItemInventario::Cemento, -1, 'Error', now());
    }

    public function test_requires_a_note(): void
    {
        $sucursal = Sucursal::factory()->create();

        $this->expectException(ReglaDeNegocio::class);

        app(AjustarInventario::class)($sucursal, ItemInventario::Cemento, 5, '', now());
    }
}
