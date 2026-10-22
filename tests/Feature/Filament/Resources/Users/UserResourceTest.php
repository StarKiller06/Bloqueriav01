<?php

namespace Tests\Feature\Filament\Resources\Users;

use App\Enums\Rol;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Empresa;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_creates_a_user_with_the_chosen_rol(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: [
                'name' => 'Encargada Sur',
                'email' => 'sur@bloqueria.test',
                'rol' => Rol::Encargado->value,
                'password' => 'secreto123',
            ])
            ->assertHasNoFormErrors();

        $nuevo = User::firstWhere('email', 'sur@bloqueria.test');

        $this->assertSame(Rol::Encargado, $nuevo->rolEn($empresa));
    }

    public function test_administrator_changes_another_users_rol(): void
    {
        $admin = User::factory()->create();
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()
            ->conUsuario($admin)
            ->conUsuario($encargado, Rol::Encargado)
            ->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($encargado), data: ['rol' => Rol::Administrador->value])
            ->assertHasNoFormErrors();

        $this->assertSame(Rol::Administrador, $encargado->fresh()->rolEn($empresa));
    }

    public function test_lists_only_users_of_the_open_empresa(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $ajeno = User::factory()->create();
        Empresa::factory()->conUsuario($ajeno)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageUsers::class)
            ->assertCanSeeTableRecords([$admin])
            ->assertCanNotSeeTableRecords([$ajeno]);
    }

    public function test_removing_a_user_detaches_it_without_deleting_the_account(): void
    {
        $admin = User::factory()->create();
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()
            ->conUsuario($admin)
            ->conUsuario($encargado, Rol::Encargado)
            ->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('quitar')->table($encargado));

        $this->assertFalse($encargado->canAccessTenant($empresa));
        $this->assertModelExists($encargado);
    }

    public function test_administrator_cannot_remove_themselves(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageUsers::class)
            ->assertActionHidden(TestAction::make('quitar')->table($admin));
    }

    public function test_forbids_encargado_from_managing_users(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();

        $this->actingAs($encargado)
            ->get(UserResource::getUrl(tenant: $empresa))
            ->assertForbidden();
    }
}
