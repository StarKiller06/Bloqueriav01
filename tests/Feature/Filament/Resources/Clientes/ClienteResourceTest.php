<?php

namespace Tests\Feature\Filament\Resources\Clientes;

use App\Enums\Rol;
use App\Enums\TipoDocumento;
use App\Filament\Resources\Clientes\Pages\ManageClientes;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClienteResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_encargado_registers_a_cliente(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();

        $this->entrarComo($encargado, $empresa);

        Livewire::test(ManageClientes::class)
            ->callAction('create', data: [
                'tipo_documento' => TipoDocumento::Nit->value,
                'documento' => '900111222-3',
                'nombre' => 'Constructora Norte S.A.S.',
                'email' => 'compras@norte.test',
            ])
            ->assertHasNoFormErrors();

        $cliente = $empresa->clientes()->sole();
        $this->assertSame(TipoDocumento::Nit, $cliente->tipo_documento);
    }

    public function test_rejects_the_same_tipo_and_documento_twice(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        Cliente::factory()->for($empresa)->create(['tipo_documento' => TipoDocumento::Cedula, 'documento' => '1010']);

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageClientes::class)
            ->callAction('create', data: ['tipo_documento' => TipoDocumento::Cedula->value, 'documento' => '1010', 'nombre' => 'Repetido'])
            ->assertHasFormErrors(['documento']);
    }

    public function test_allows_the_same_number_with_a_different_tipo(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        Cliente::factory()->for($empresa)->create(['tipo_documento' => TipoDocumento::Cedula, 'documento' => '1010']);

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageClientes::class)
            ->callAction('create', data: ['tipo_documento' => TipoDocumento::Pasaporte->value, 'documento' => '1010', 'nombre' => 'Otro'])
            ->assertHasNoFormErrors();
    }

    public function test_finds_a_cliente_by_documento(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $buscado = Cliente::factory()->for($empresa)->create(['documento' => '5551234']);
        $otro = Cliente::factory()->for($empresa)->create(['documento' => '7779999']);
        $ajeno = Cliente::factory()->create(['documento' => '5551234']);

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageClientes::class)
            ->searchTable('5551234')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro, $ajeno]);
    }

    public function test_encargado_cannot_delete_a_cliente(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();
        $cliente = Cliente::factory()->for($empresa)->create();

        $this->entrarComo($encargado, $empresa);

        Livewire::test(ManageClientes::class)
            ->assertActionHidden(TestAction::make('delete')->table($cliente));
    }
}
