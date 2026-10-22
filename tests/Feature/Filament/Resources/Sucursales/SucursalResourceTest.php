<?php

namespace Tests\Feature\Filament\Resources\Sucursales;

use App\Enums\Rol;
use App\Filament\Resources\Sucursales\Pages\ManageSucursales;
use App\Filament\Resources\Sucursales\SucursalResource;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Trabajador;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SucursalResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_the_sucursales_of_the_open_empresa(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user)->create();
        $propias = Sucursal::factory()->count(2)->for($empresa)->create();
        $ajena = Sucursal::factory()->create();

        $this->entrarComo($user, $empresa);

        Livewire::test(ManageSucursales::class)
            ->assertCanSeeTableRecords($propias)
            ->assertCanNotSeeTableRecords([$ajena]);
    }

    public function test_administrator_creates_a_sucursal_inside_the_open_empresa(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user)->create();

        $this->entrarComo($user, $empresa);

        Livewire::test(ManageSucursales::class)
            ->callAction('create', data: ['nombre' => 'Sucursal Norte', 'direccion' => 'Calle 1'])
            ->assertHasNoFormErrors();

        $this->assertTrue($empresa->sucursales()->where('nombre', 'Sucursal Norte')->exists());
    }

    public function test_rejects_a_repeated_name_within_the_same_empresa(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user)->create();
        Sucursal::factory()->for($empresa)->create(['nombre' => 'Sucursal Norte']);

        $this->entrarComo($user, $empresa);

        Livewire::test(ManageSucursales::class)
            ->callAction('create', data: ['nombre' => 'Sucursal Norte'])
            ->assertHasFormErrors(['nombre']);

        $this->assertSame(1, $empresa->sucursales()->count());
    }

    public function test_allows_the_same_name_in_another_empresa(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user)->create();
        Sucursal::factory()->create(['nombre' => 'Sucursal Norte']);

        $this->entrarComo($user, $empresa);

        Livewire::test(ManageSucursales::class)
            ->callAction('create', data: ['nombre' => 'Sucursal Norte'])
            ->assertHasNoFormErrors();
    }

    public function test_encargado_can_see_but_not_change_sucursales(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user, Rol::Encargado)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();

        $this->entrarComo($user, $empresa);

        Livewire::test(ManageSucursales::class)
            ->assertCanSeeTableRecords([$sucursal])
            ->assertActionHidden('create')
            ->assertActionHidden(TestAction::make('edit')->table($sucursal))
            ->assertActionHidden(TestAction::make('delete')->table($sucursal));
    }

    public function test_a_sucursal_with_trabajadores_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user)->create();
        $conTrabajadores = Sucursal::factory()->for($empresa)->create();
        Trabajador::factory()->for($conTrabajadores)->create();
        $vacia = Sucursal::factory()->for($empresa)->create();

        $this->entrarComo($user, $empresa);

        Livewire::test(ManageSucursales::class)
            ->assertActionHidden(TestAction::make('delete')->table($conTrabajadores))
            ->assertActionVisible(TestAction::make('delete')->table($vacia));
    }

    public function test_forbids_opening_another_empresa_the_user_does_not_belong_to(): void
    {
        $user = User::factory()->create();
        Empresa::factory()->conUsuario($user)->create();
        $ajena = Empresa::factory()->create();

        $this->actingAs($user)
            ->get(SucursalResource::getUrl(tenant: $ajena))
            ->assertNotFound();
    }
}
