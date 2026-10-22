<?php

namespace Tests\Feature\Filament\Resources\Trabajadores;

use App\Enums\Rol;
use App\Filament\Resources\Trabajadores\Pages\ManageTrabajadores;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Trabajador;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrabajadorResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_encargado_registers_a_trabajador_in_a_sucursal(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();

        $this->entrarComo($encargado, $empresa);

        Livewire::test(ManageTrabajadores::class)
            ->callAction('create', data: [
                'sucursal_id' => $sucursal->id,
                'nombre' => 'Trabajador Nuevo',
                'documento' => '1098765432',
            ])
            ->assertHasNoFormErrors();

        $trabajador = $empresa->trabajadores()->sole();
        $this->assertTrue($trabajador->sucursal->is($sucursal));
        $this->assertTrue($trabajador->activo);
    }

    public function test_rejects_a_sucursal_from_another_empresa(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $sucursalAjena = Sucursal::factory()->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageTrabajadores::class)
            ->callAction('create', data: [
                'sucursal_id' => $sucursalAjena->id,
                'nombre' => 'Trabajador Nuevo',
                'documento' => '1098765432',
            ])
            ->assertHasFormErrors(['sucursal_id']);

        $this->assertSame(0, Trabajador::count());
    }

    public function test_rejects_a_documento_already_registered_in_the_empresa(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        Trabajador::factory()->for($sucursal)->create(['documento' => '1098765432']);

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageTrabajadores::class)
            ->callAction('create', data: [
                'sucursal_id' => $sucursal->id,
                'nombre' => 'Otro',
                'documento' => '1098765432',
            ])
            ->assertHasFormErrors(['documento']);
    }

    public function test_hides_inactive_trabajadores_by_default(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        $activo = Trabajador::factory()->for($sucursal)->create();
        $inactivo = Trabajador::factory()->for($sucursal)->inactivo()->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageTrabajadores::class)
            ->assertCanSeeTableRecords([$activo])
            ->assertCanNotSeeTableRecords([$inactivo]);
    }

    public function test_lists_only_trabajadores_of_the_open_empresa(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $propio = Trabajador::factory()->for(Sucursal::factory()->for($empresa))->create();
        $ajeno = Trabajador::factory()->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageTrabajadores::class)
            ->assertCanSeeTableRecords([$propio])
            ->assertCanNotSeeTableRecords([$ajeno]);
    }

    public function test_encargado_cannot_delete_a_trabajador(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();
        $trabajador = Trabajador::factory()->for(Sucursal::factory()->for($empresa))->create();

        $this->entrarComo($encargado, $empresa);

        Livewire::test(ManageTrabajadores::class)
            ->assertActionVisible(TestAction::make('edit')->table($trabajador))
            ->assertActionHidden(TestAction::make('delete')->table($trabajador));
    }
}
