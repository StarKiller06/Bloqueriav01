<?php

namespace Tests\Feature\Actions\Reportes;

use App\Actions\Arranque\CerrarCargaInicial;
use App\Actions\Arranque\GuardarCargaInicial;
use App\Actions\Caja\RegistrarMovimiento;
use App\Actions\Produccion\AnularProduccion;
use App\Actions\Produccion\RegistrarProduccion;
use App\Actions\Reportes\CerrarMes;
use App\Actions\Reportes\ReabrirMes;
use App\Exceptions\ReglaDeNegocio;
use App\Models\CargaInicial;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CerrarMesTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Sucursal $sucursal;

    private CargaInicial $carga;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-05'));

        $this->empresa = Empresa::factory()->create();
        $this->sucursal = Sucursal::factory()->for($this->empresa)->create();
        $this->carga = app(GuardarCargaInicial::class)($this->empresa, Carbon::create(2026, 9, 1), 500000, [$this->sucursal->id => ['bloques' => 100, 'cemento' => 10, 'arena_bloques' => 1000]]);
    }

    public function test_closing_saves_the_report_and_blocks_movements_dated_in_that_month(): void
    {
        app(CerrarCargaInicial::class)($this->carga);

        $cierre = app(CerrarMes::class)($this->empresa, 2026, 9);

        $this->assertEquals(500000, $cierre->reporte['caja']['saldo_final']);

        try {
            app(RegistrarMovimiento::class)($this->empresa->conceptosCaja()->firstWhere('nombre', 'Luz'), 1000, Carbon::create(2026, 9, 15));
            $this->fail('Debió rechazar el movimiento.');
        } catch (ReglaDeNegocio $e) {
            $this->assertStringContainsString('Septiembre de 2026 ya está cerrado', $e->getMessage());
        }

        $this->expectException(ReglaDeNegocio::class);

        app(RegistrarProduccion::class)(Trabajador::factory()->for($this->sucursal)->create(), $this->empresa->productos()->sole(), 1, 100, Carbon::create(2026, 9, 20));
    }

    public function test_the_saved_report_does_not_change_when_later_months_do(): void
    {
        app(CerrarCargaInicial::class)($this->carga);
        $cierre = app(CerrarMes::class)($this->empresa, 2026, 9);

        app(RegistrarMovimiento::class)($this->empresa->conceptosCaja()->firstWhere('nombre', 'Luz'), 1000, Carbon::create(2026, 10, 15));

        $this->assertEquals(500000, $cierre->fresh()->reporte['caja']['saldo_final']);
    }

    public function test_requires_closing_the_carga_inicial_first(): void
    {
        $this->expectExceptionMessage('carga inicial');

        app(CerrarMes::class)($this->empresa, 2026, 9);
    }

    public function test_months_are_closed_in_order(): void
    {
        app(CerrarCargaInicial::class)($this->carga);

        $this->expectExceptionMessage('septiembre de 2026');

        app(CerrarMes::class)($this->empresa, 2026, 10);
    }

    public function test_the_current_month_cannot_be_closed(): void
    {
        app(CerrarCargaInicial::class)($this->carga);
        app(CerrarMes::class)($this->empresa, 2026, 9);
        app(CerrarMes::class)($this->empresa, 2026, 10);

        $this->expectExceptionMessage('todavía no ha terminado');

        app(CerrarMes::class)($this->empresa, 2026, 11);
    }

    public function test_only_the_last_closed_month_can_be_reopened(): void
    {
        app(CerrarCargaInicial::class)($this->carga);
        $septiembre = app(CerrarMes::class)($this->empresa, 2026, 9);
        $octubre = app(CerrarMes::class)($this->empresa, 2026, 10);

        try {
            app(ReabrirMes::class)($septiembre);
            $this->fail('Debió rechazar reabrir septiembre.');
        } catch (ReglaDeNegocio) {
            //
        }

        app(ReabrirMes::class)($octubre);

        $this->assertFalse($this->empresa->mesCerrado(Carbon::create(2026, 10, 1)));
        $this->assertTrue($this->empresa->mesCerrado(Carbon::create(2026, 9, 1)));
    }

    public function test_a_production_in_a_closed_month_cannot_be_annulled(): void
    {
        $produccion = app(RegistrarProduccion::class)(Trabajador::factory()->for($this->sucursal)->create(), $this->empresa->productos()->sole(), 1, 100, Carbon::create(2026, 9, 20));
        app(CerrarCargaInicial::class)($this->carga);
        app(CerrarMes::class)($this->empresa, 2026, 9);

        $this->expectException(ReglaDeNegocio::class);

        app(AnularProduccion::class)($produccion);
    }
}
