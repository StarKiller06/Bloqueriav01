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
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PagoTrabajadorResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pays_every_pending_day_from_the_trabajadores_list_by_default(): void
    {
        [$empresa, $trabajador] = $this->escenario();
        $lunes = Produccion::factory()->for($trabajador)->resultado(4, 412)->create();
        $martes = Produccion::factory()->for($trabajador)->resultado(3, 284)->create();

        Livewire::test(ManageTrabajadores::class)
            ->mountAction(TestAction::make('pagar')->table($trabajador))
            ->assertActionDataSet(['producciones' => [$lunes->id, $martes->id]])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        $pago = $trabajador->pagos()->sole();
        $this->assertSame(7, $pago->bultos_pagados);
        $this->assertSame('105000.00', $pago->monto);
        $this->assertSame(-105000.0, $empresa->saldoCaja());
    }

    public function test_hides_the_pay_button_when_nothing_is_pending(): void
    {
        [, $trabajador] = $this->escenario();

        Livewire::test(ManageTrabajadores::class)
            ->assertActionHidden(TestAction::make('pagar')->table($trabajador));
    }

    public function test_pays_only_some_days_from_the_pagos_page(): void
    {
        [$empresa, $trabajador] = $this->escenario();
        Produccion::factory()->for($trabajador)->resultado(4, 412)->create();
        $martes = Produccion::factory()->for($trabajador)->resultado(3, 284)->create();

        Livewire::test(ManagePagosTrabajador::class)
            ->callAction('pagar', data: [
                'trabajador_id' => $trabajador->id,
                'producciones' => [$martes->id],
                'fecha' => now()->toDateString(),
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(3, $trabajador->pagos()->sole()->bultos_pagados);
        $this->assertSame(-45000.0, $empresa->saldoCaja());
        $this->assertSame(4, $trabajador->bultosPorPagar());
    }

    public function test_lists_every_active_trabajador_and_only_enables_those_with_pending_bultos(): void
    {
        [, $conPendiente] = $this->escenario();
        $sinPendiente = Trabajador::factory()->for($conPendiente->sucursal)->create(['nombre' => 'Sin pendiente']);
        Produccion::factory()->for($conPendiente)->resultado(4, 412)->create();

        Livewire::test(ManagePagosTrabajador::class)
            ->mountAction('pagar')
            ->assertFormFieldExists('trabajador_id', 'mountedActionSchema0', fn (Select $campo): bool => str_contains($campo->getOptions()[$conPendiente->id], '4 bultos por pagar')
                && ! $campo->isOptionDisabled($conPendiente->id, $campo->getOptions()[$conPendiente->id])
                && $campo->isOptionDisabled($sinPendiente->id, $campo->getOptions()[$sinPendiente->id]));
    }

    public function test_explains_what_to_do_when_nobody_has_pending_production(): void
    {
        $this->escenario();

        Livewire::test(ManagePagosTrabajador::class)
            ->mountAction('pagar')
            ->assertFormFieldExists('trabajador_id', 'mountedActionSchema0', fn (Select $campo): bool => str_contains(
                (string) $campo->getChildSchema($campo::BELOW_CONTENT_SCHEMA_KEY)?->getComponents()[0]?->getContent(),
                'Nadie tiene producción pendiente por pagar',
            ));
    }

    public function test_explains_that_the_tarifa_must_be_configured(): void
    {
        [$empresa, $trabajador] = $this->escenario();
        $empresa->update(['tarifa_por_bulto' => 0]);
        $lunes = Produccion::factory()->for($trabajador)->resultado(4, 412)->create();

        Livewire::test(ManagePagosTrabajador::class)
            ->callAction('pagar', data: [
                'trabajador_id' => $trabajador->id,
                'producciones' => [$lunes->id],
                'fecha' => now()->toDateString(),
            ])
            ->assertNotified('No se pudo guardar');

        $this->assertSame(0, $trabajador->pagos()->count());
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
