<?php

namespace Tests\Feature\Actions\Inventario;

use App\Actions\Inventario\RegistrarCompraCemento;
use App\Enums\ConceptoSistema;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Empresa;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarCompraCementoTest extends TestCase
{
    use RefreshDatabase;

    public function test_splits_the_bultos_between_sucursales_and_pays_from_the_caja(): void
    {
        $empresa = Empresa::factory()->create();
        [$norte, $sur] = Sucursal::factory()->count(2)->for($empresa)->create();

        $compra = app(RegistrarCompraCemento::class)($empresa, now(), 30, 32000, [$norte->id => 18, $sur->id => 12], 'Cementos del Valle');

        $this->assertSame(18, $norte->stock(ItemInventario::Cemento));
        $this->assertSame(12, $sur->stock(ItemInventario::Cemento));
        $this->assertSame(30, $empresa->stockGeneral(ItemInventario::Cemento));
        $this->assertSame(-960000.0, $empresa->saldoCaja());
        $this->assertTrue($empresa->movimientosCaja()->sole()->concepto->is($empresa->conceptoSistema(ConceptoSistema::CompraCemento)));
        $this->assertCount(2, $compra->repartos);
    }

    public function test_rejects_a_reparto_that_does_not_add_up_to_the_compra(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();

        try {
            app(RegistrarCompraCemento::class)($empresa, now(), 30, 32000, [$sucursal->id => 25]);
            $this->fail('Debió rechazar el reparto.');
        } catch (ReglaDeNegocio $e) {
            $this->assertStringContainsString('25', $e->getMessage());
        }

        $this->assertSame(0, $empresa->comprasCemento()->count());
        $this->assertSame(0.0, $empresa->saldoCaja());
    }

    public function test_rejects_a_sucursal_of_another_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $ajena = Sucursal::factory()->create();

        $this->expectException(ReglaDeNegocio::class);

        app(RegistrarCompraCemento::class)($empresa, now(), 10, 32000, [$ajena->id => 10]);
    }
}
