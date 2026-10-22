<?php

namespace Tests\Feature\Filament\Resources\MovimientosCaja;

use App\Actions\Caja\RegistrarMovimiento;
use App\Enums\ConceptoSistema;
use App\Enums\Rol;
use App\Enums\TipoMovimiento;
use App\Filament\Resources\MovimientosCaja\Pages\ManageMovimientosCaja;
use App\Models\ConceptoCaja;
use App\Models\Empresa;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovimientoCajaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_encargado_registers_a_gasto_that_subtracts_from_the_caja(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();
        $luz = $empresa->conceptosCaja()->firstWhere('nombre', 'Luz');
        $sucursal = Sucursal::factory()->for($empresa)->create();

        $this->entrarComo($encargado, $empresa);

        Livewire::test(ManageMovimientosCaja::class)
            ->callAction('registrar_gasto', data: [
                'concepto_caja_id' => $luz->id,
                'monto' => 85000,
                'fecha' => now()->toDateString(),
                'sucursal_id' => $sucursal->id,
                'nota' => 'Factura de octubre',
            ])
            ->assertHasNoFormErrors();

        $movimiento = $empresa->movimientosCaja()->sole();
        $this->assertSame(TipoMovimiento::Gasto, $movimiento->tipo);
        $this->assertTrue($movimiento->user->is($encargado));
        $this->assertSame(-85000.0, $empresa->saldoCaja());
    }

    public function test_registers_an_entrada_without_sucursal(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $arena = $empresa->conceptosCaja()->firstWhere('nombre', 'Venta de arena');

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageMovimientosCaja::class)
            ->callAction('registrar_entrada', data: [
                'concepto_caja_id' => $arena->id,
                'monto' => 120000,
                'fecha' => now()->toDateString(),
            ])
            ->assertHasNoFormErrors();

        $this->assertNull($empresa->movimientosCaja()->sole()->sucursal_id);
        $this->assertSame(120000.0, $empresa->saldoCaja());
    }

    public function test_rejects_a_concepto_of_the_other_tipo(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $arena = $empresa->conceptosCaja()->firstWhere('nombre', 'Venta de arena');

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageMovimientosCaja::class)
            ->callAction('registrar_gasto', data: [
                'concepto_caja_id' => $arena->id,
                'monto' => 1000,
                'fecha' => now()->toDateString(),
            ])
            ->assertHasFormErrors(['concepto_caja_id']);

        $this->assertSame(0, MovimientoCaja::count());
    }

    public function test_rejects_a_system_concepto_in_the_manual_form(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageMovimientosCaja::class)
            ->callAction('registrar_entrada', data: [
                'concepto_caja_id' => $empresa->conceptoSistema(ConceptoSistema::VentaContado)->id,
                'monto' => 1000,
                'fecha' => now()->toDateString(),
            ])
            ->assertHasFormErrors(['concepto_caja_id']);
    }

    public function test_rejects_a_future_date_and_a_non_positive_amount(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageMovimientosCaja::class)
            ->callAction('registrar_gasto', data: [
                'concepto_caja_id' => $empresa->conceptosCaja()->firstWhere('nombre', 'Luz')->id,
                'monto' => 0,
                'fecha' => now()->addDay()->toDateString(),
            ])
            ->assertHasFormErrors(['monto', 'fecha']);
    }

    public function test_lists_only_movements_of_the_open_empresa(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $propio = MovimientoCaja::factory()->for(ConceptoCaja::factory()->for($empresa), 'concepto')->create();
        $ajeno = MovimientoCaja::factory()->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageMovimientosCaja::class)
            ->assertCanSeeTableRecords([$propio])
            ->assertCanNotSeeTableRecords([$ajeno]);
    }

    public function test_administrator_corrects_a_manual_movement(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $movimiento = MovimientoCaja::factory()
            ->for($empresa->conceptosCaja()->firstWhere('nombre', 'Luz'), 'concepto')
            ->create(['monto' => 80000]);

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageMovimientosCaja::class)
            ->callAction(TestAction::make('edit')->table($movimiento), data: ['monto' => 85000])
            ->assertHasNoFormErrors();

        $this->assertSame('85000.00', $movimiento->fresh()->monto);
        $this->assertSame(TipoMovimiento::Gasto, $movimiento->fresh()->tipo);
    }

    public function test_encargado_cannot_edit_or_delete_movements(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();
        $movimiento = MovimientoCaja::factory()
            ->for($empresa->conceptosCaja()->firstWhere('nombre', 'Luz'), 'concepto')
            ->create();

        $this->entrarComo($encargado, $empresa);

        Livewire::test(ManageMovimientosCaja::class)
            ->assertActionHidden(TestAction::make('edit')->table($movimiento))
            ->assertActionHidden(TestAction::make('delete')->table($movimiento));
    }

    public function test_movements_from_other_modules_cannot_be_edited_here(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $movimiento = (new RegistrarMovimiento)($empresa->conceptoSistema(ConceptoSistema::ViajeArena), 700000, now());

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageMovimientosCaja::class)
            ->assertActionHidden(TestAction::make('edit')->table($movimiento))
            ->assertActionHidden(TestAction::make('delete')->table($movimiento));
    }
}
