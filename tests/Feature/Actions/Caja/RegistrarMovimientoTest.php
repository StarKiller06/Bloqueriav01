<?php

namespace Tests\Feature\Actions\Caja;

use App\Actions\Caja\RegistrarMovimiento;
use App\Enums\ConceptoSistema;
use App\Enums\TipoMovimiento;
use App\Models\ConceptoCaja;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RegistrarMovimientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_the_movement_with_the_tipo_of_its_concepto(): void
    {
        $empresa = Empresa::factory()->create();
        $concepto = ConceptoCaja::factory()->for($empresa)->gasto()->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        $user = User::factory()->create();

        $movimiento = (new RegistrarMovimiento)($concepto, 45000, now(), $user, $sucursal, 'Recibo 123');

        $this->assertSame(TipoMovimiento::Gasto, $movimiento->tipo);
        $this->assertTrue($movimiento->empresa->is($empresa));
        $this->assertTrue($movimiento->sucursal->is($sucursal));
        $this->assertTrue($movimiento->user->is($user));
        $this->assertSame('45000.00', $movimiento->monto);
    }

    public function test_links_the_movement_to_the_record_that_originated_it(): void
    {
        $empresa = Empresa::factory()->create();
        $origen = Sucursal::factory()->for($empresa)->create();

        $movimiento = (new RegistrarMovimiento)($empresa->conceptoSistema(ConceptoSistema::ViajeArena), 700000, now(), origen: $origen);

        $this->assertTrue($movimiento->origen->is($origen));
        $this->assertFalse($movimiento->esManual());
    }

    public function test_rejects_an_amount_of_zero(): void
    {
        $concepto = ConceptoCaja::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        (new RegistrarMovimiento)($concepto, 0, now());
    }

    public function test_rejects_a_sucursal_of_another_empresa(): void
    {
        $concepto = ConceptoCaja::factory()->create();
        $sucursalAjena = Sucursal::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        (new RegistrarMovimiento)($concepto, 1000, now(), sucursal: $sucursalAjena);
    }
}
