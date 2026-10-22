<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\ResumenCaja;
use App\Models\Empresa;
use App\Models\MovimientoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Tests\TestCase;

class ResumenCajaTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_the_saldo_and_only_this_months_entradas_and_gastos(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15));

        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $arena = $empresa->conceptosCaja()->firstWhere('nombre', 'Venta de arena');
        $luz = $empresa->conceptosCaja()->firstWhere('nombre', 'Luz');
        MovimientoCaja::factory()->for($arena, 'concepto')->create(['monto' => 200000, 'fecha' => '2026-09-30']);
        MovimientoCaja::factory()->for($arena, 'concepto')->create(['monto' => 50000, 'fecha' => '2026-10-02']);
        MovimientoCaja::factory()->for($luz, 'concepto')->create(['monto' => 30000, 'fecha' => '2026-10-10']);

        $this->entrarComo($admin, $empresa);

        Livewire::test(ResumenCaja::class)
            ->assertSeeTextInOrder([
                'Saldo actual', $this->pesos(220000),
                'Entradas de octubre', $this->pesos(50000),
                'Gastos de octubre', $this->pesos(30000),
            ]);
    }

    /**
     * El formato colombiano usa un espacio que no se parte entre "$" y el número.
     */
    private function pesos(float $valor): string
    {
        return Number::currency($valor, 'COP', 'es_CO', 0);
    }
}
