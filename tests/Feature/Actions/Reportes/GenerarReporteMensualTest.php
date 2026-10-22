<?php

namespace Tests\Feature\Actions\Reportes;

use App\Actions\Arranque\GuardarCargaInicial;
use App\Actions\Caja\RegistrarMovimiento;
use App\Actions\Inventario\RegistrarCompraCemento;
use App\Actions\Inventario\RegistrarViajeArena;
use App\Actions\Produccion\PagarTrabajador;
use App\Actions\Produccion\RegistrarProduccion;
use App\Actions\Reportes\GenerarReporteMensual;
use App\Actions\Ventas\RegistrarAbono;
use App\Actions\Ventas\RegistrarVenta;
use App\Enums\FormaPago;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GenerarReporteMensualTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Sucursal $sucursal;

    /**
     * Septiembre: carga inicial. Octubre: compra, viaje, producción, pago, ventas y un abono.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-05'));

        $this->empresa = Empresa::factory()->create(['tarifa_por_bulto' => 15000, 'bloques_por_viaje' => 1200]);
        $this->sucursal = Sucursal::factory()->for($this->empresa)->create(['nombre' => 'Norte']);
        $bloque = $this->empresa->productos()->sole();
        $cliente = Cliente::factory()->for($this->empresa)->create(['nombre' => 'Constructora']);
        $trabajador = Trabajador::factory()->for($this->sucursal)->create(['nombre' => 'Trabajador A']);
        $octubre = fn (int $dia): Carbon => Carbon::create(2026, 10, $dia);

        app(GuardarCargaInicial::class)($this->empresa, Carbon::create(2026, 9, 30), 1000000, [$this->sucursal->id => ['bloques' => 200, 'cemento' => 2, 'arena_bloques' => 0]]);
        app(RegistrarCompraCemento::class)($this->empresa, $octubre(1), 10, 30000, [$this->sucursal->id => 10]);
        app(RegistrarViajeArena::class)($this->sucursal, $octubre(1), 600000);
        app(RegistrarProduccion::class)($trabajador, $bloque, 4, 412, $octubre(2));
        app(PagarTrabajador::class)($trabajador, $octubre(3));
        app(RegistrarVenta::class)($this->sucursal, [['producto' => $bloque, 'cantidad' => 100, 'precio' => 2000]], FormaPago::Contado, $octubre(5));
        $credito = app(RegistrarVenta::class)($this->sucursal, [['producto' => $bloque, 'cantidad' => 200, 'precio' => 1900]], FormaPago::Credito, $octubre(6), $cliente);
        app(RegistrarAbono::class)($credito, 100000, $octubre(20));
        app(RegistrarAbono::class)($credito, 50000, Carbon::create(2026, 11, 2));
        app(RegistrarMovimiento::class)($this->empresa->conceptosCaja()->firstWhere('nombre', 'Luz'), 70000, $octubre(10));
    }

    public function test_caja_starts_with_septembers_balance_and_lists_october_by_concepto(): void
    {
        $caja = $this->reporte()['caja'];

        $this->assertSame(1000000.0, $caja['saldo_inicial']);
        $this->assertSame(300000.0, $caja['total_entradas']);
        $this->assertSame(300000 + 600000 + 60000 + 70000, (int) $caja['total_gastos']);
        $this->assertSame(1000000.0 + 300000 - 1030000, $caja['saldo_final']);
        $this->assertContains(['concepto' => 'Venta de contado', 'total' => 200000.0], $caja['entradas']);
    }

    public function test_inventory_shows_how_each_item_moved_in_the_month(): void
    {
        $norte = $this->reporte()['inventario']['sucursales'][0];

        $this->assertSame(['inicial' => 200, 'apertura' => 0, 'produccion' => 412, 'ventas' => -300, 'traslados' => 0, 'ajustes' => 0, 'final' => 312], $norte['bloques']);
        $this->assertSame(['inicial' => 2, 'apertura' => 0, 'compras' => 10, 'consumo' => -4, 'ajustes' => 0, 'final' => 8], $norte['cemento']);
    }

    public function test_cartera_is_what_was_owed_at_the_end_of_the_month(): void
    {
        $ventas = $this->reporte()['ventas'];

        $this->assertSame([['cliente' => 'Constructora', 'saldo' => 280000.0]], $ventas['cartera']);
        $this->assertSame(100000.0, $ventas['abonos']);
        $this->assertSame(200000.0, $ventas['total_contado']);
        $this->assertSame(380000.0, $ventas['total_credito']);
    }

    public function test_trabajadores_show_their_month(): void
    {
        $this->assertSame(
            [['nombre' => 'Trabajador A', 'sucursal' => 'Norte', 'bultos' => 4, 'sacados' => 412, 'diferencia' => 12, 'pagado' => 60000.0]],
            $this->reporte()['trabajadores'],
        );
    }

    public function test_estimates_the_cost_of_each_bloque(): void
    {
        $costo = $this->reporte()['costo_bloque'];

        $this->assertEqualsWithDelta(4 * 30000 / 412, $costo['cemento'], 0.01);
        $this->assertEqualsWithDelta(600000 / 1200, $costo['arena'], 0.01);
        $this->assertEqualsWithDelta(4 * 15000 / 412, $costo['mano_de_obra'], 0.01);
        $this->assertEqualsWithDelta((200000 + 380000) / 300, $costo['precio_promedio_venta'], 0.01);
    }

    /**
     * @return array<string, mixed>
     */
    private function reporte(): array
    {
        return app(GenerarReporteMensual::class)($this->empresa, 2026, 10);
    }
}
