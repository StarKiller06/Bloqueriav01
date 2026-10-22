<?php

namespace Tests\Feature\Actions\Produccion;

use App\Actions\Produccion\RegistrarProduccion;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarProduccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_four_bultos_and_412_bloques_is_twelve_above_the_standard(): void
    {
        [$trabajador, $sucursal, $empresa] = $this->trabajadorConCemento(10);

        $produccion = app(RegistrarProduccion::class)($trabajador, $empresa->productos()->sole(), 4, 412, now());

        $this->assertSame(400, $produccion->bloques_esperados);
        $this->assertSame(12, $produccion->diferencia);
        $this->assertSame(6, $sucursal->stock(ItemInventario::Cemento));
        $this->assertSame(412, $sucursal->stock(ItemInventario::Bloque));
    }

    public function test_does_not_touch_the_caja(): void
    {
        [$trabajador, , $empresa] = $this->trabajadorConCemento(10);

        app(RegistrarProduccion::class)($trabajador, $empresa->productos()->sole(), 4, 412, now());

        $this->assertSame(0, $empresa->movimientosCaja()->count());
    }

    public function test_accumulates_the_difference_of_each_trabajador(): void
    {
        [$trabajador, , $empresa] = $this->trabajadorConCemento(20);
        $bloque = $empresa->productos()->sole();
        $registrar = app(RegistrarProduccion::class);

        $registrar($trabajador, $bloque, 4, 412, now());
        $registrar($trabajador, $bloque, 3, 284, now());
        $registrar($trabajador, $bloque, 5, 507, now());

        $this->assertSame(3, $trabajador->rendimientoAcumulado());
    }

    public function test_uses_the_empresas_own_standard(): void
    {
        [$trabajador, , $empresa] = $this->trabajadorConCemento(10);
        $empresa->update(['bloques_por_bulto' => 90]);

        $produccion = app(RegistrarProduccion::class)($trabajador->fresh(), $empresa->productos()->sole(), 2, 180, now());

        $this->assertSame(0, $produccion->diferencia);
    }

    public function test_rejects_using_more_cemento_than_the_sucursal_has(): void
    {
        [$trabajador, $sucursal, $empresa] = $this->trabajadorConCemento(3);

        try {
            app(RegistrarProduccion::class)($trabajador, $empresa->productos()->sole(), 4, 400, now());
            $this->fail('Debió rechazar la producción.');
        } catch (ReglaDeNegocio $e) {
            $this->assertStringContainsString('hay 3 bultos', $e->getMessage());
        }

        $this->assertSame(0, $empresa->producciones()->count());
        $this->assertSame(0, $sucursal->stock(ItemInventario::Bloque));
    }

    public function test_rejects_an_inactive_trabajador(): void
    {
        [$trabajador, , $empresa] = $this->trabajadorConCemento(10);
        $trabajador->update(['activo' => false]);

        $this->expectException(ReglaDeNegocio::class);

        app(RegistrarProduccion::class)($trabajador, $empresa->productos()->sole(), 1, 100, now());
    }

    /**
     * @return array{Trabajador, Sucursal, Empresa}
     */
    private function trabajadorConCemento(int $bultos): array
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        MovimientoInventario::factory()->for($sucursal)->cemento($bultos)->create();

        return [Trabajador::factory()->for($sucursal)->create(), $sucursal, $empresa];
    }
}
