<?php

namespace Tests\Feature\Models;

use App\Enums\ConceptoSistema;
use App\Models\ConceptoCaja;
use App\Models\Empresa;
use App\Models\MovimientoCaja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpresaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_empresa_has_every_system_concepto_and_the_common_manual_ones(): void
    {
        $empresa = Empresa::factory()->create();

        foreach (ConceptoSistema::cases() as $clave) {
            $this->assertSame($clave->tipo(), $empresa->conceptoSistema($clave)->tipo);
        }

        $this->assertEqualsCanonicalizing(
            array_keys(ConceptoCaja::MANUALES_INICIALES),
            $empresa->conceptosCaja()->manuales()->pluck('nombre')->all(),
        );
    }

    public function test_saldo_is_entradas_minus_gastos_of_its_own_caja(): void
    {
        $empresa = Empresa::factory()->create();
        $entrada = ConceptoCaja::factory()->for($empresa)->entrada()->create();
        $gasto = ConceptoCaja::factory()->for($empresa)->gasto()->create();
        MovimientoCaja::factory()->for($entrada, 'concepto')->create(['monto' => 100000]);
        MovimientoCaja::factory()->for($entrada, 'concepto')->create(['monto' => 50000]);
        MovimientoCaja::factory()->for($gasto, 'concepto')->create(['monto' => 30000]);
        MovimientoCaja::factory()->create(['monto' => 999999]);

        $this->assertSame(120000.0, $empresa->saldoCaja());
    }

    public function test_saldo_of_an_empty_caja_is_zero(): void
    {
        $this->assertSame(0.0, Empresa::factory()->create()->saldoCaja());
    }
}
