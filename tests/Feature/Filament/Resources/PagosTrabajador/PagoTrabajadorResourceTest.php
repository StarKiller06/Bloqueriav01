<?php

namespace Tests\Feature\Filament\Resources\PagosTrabajador;

use App\Filament\Resources\PagosTrabajador\Pages\ManagePagosTrabajador;
use App\Filament\Resources\Trabajadores\Pages\ManageTrabajadores;
use App\Models\Empresa;
use App\Models\Produccion;
use App\Models\Sucursal;
use App\Models\Trabajador;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PagoTrabajadorResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pays_from_the_trabajadores_list_with_the_suggested_amount(): void
    {
        [$empresa, $trabajador] = $this->escenario();
        Produccion::factory()->for($trabajador)->resultado(4, 412)->create();

        Livewire::test(ManageTrabajadores::class)
            ->mountAction(TestAction::make('pagar')->table($trabajador))
            ->assertActionDataSet(['monto' => 60000.0])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        $this->assertSame('60000.00', $trabajador->pagos()->sole()->monto);
        $this->assertSame(-60000.0, $empresa->saldoCaja());
    }

    public function test_hides_the_pay_button_when_nothing_is_pending(): void
    {
        [, $trabajador] = $this->escenario();

        Livewire::test(ManageTrabajadores::class)
            ->assertActionHidden(TestAction::make('pagar')->table($trabajador));
    }

    public function test_pays_an_adjusted_amount_from_the_pagos_page(): void
    {
        [$empresa, $trabajador] = $this->escenario();
        Produccion::factory()->for($trabajador)->resultado(4, 412)->create();

        Livewire::test(ManagePagosTrabajador::class)
            ->callAction('pagar', data: [
                'trabajador_id' => $trabajador->id,
                'fecha' => now()->toDateString(),
                'monto' => 62000,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(-62000.0, $empresa->saldoCaja());
    }

    public function test_shows_the_trabajador_rendimiento_in_the_list(): void
    {
        [, $trabajador] = $this->escenario();
        Produccion::factory()->for($trabajador)->resultado(4, 412)->create();
        Produccion::factory()->for($trabajador)->resultado(3, 284)->create();

        Livewire::test(ManageTrabajadores::class)
            ->assertTableColumnStateSet('acumulado', -4, $trabajador)
            ->assertTableColumnStateSet('bultos_por_pagar', 7, $trabajador);
    }

    /**
     * @return array{Empresa, Trabajador}
     */
    private function escenario(): array
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create(['tarifa_por_bulto' => 15000]);
        $trabajador = Trabajador::factory()->for(Sucursal::factory()->for($empresa))->create();

        $this->entrarComo($admin, $empresa);

        return [$empresa, $trabajador];
    }
}
