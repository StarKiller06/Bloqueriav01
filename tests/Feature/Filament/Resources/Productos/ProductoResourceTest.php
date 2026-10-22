<?php

namespace Tests\Feature\Filament\Resources\Productos;

use App\Enums\Rol;
use App\Filament\Resources\Productos\Pages\ListProductos;
use App\Filament\Resources\Productos\Pages\ViewProducto;
use App\Models\Empresa;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductoResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_adjusts_the_price_and_it_appears_in_the_history(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $bloque = $empresa->productos()->sole();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ListProductos::class)
            ->callAction(TestAction::make('ajustarPrecio')->table($bloque), data: ['precio_nuevo' => 2100])
            ->assertHasNoFormErrors();

        $this->assertSame('2100.00', $bloque->fresh()->precio_vigente);
        $this->assertTrue($bloque->historialPrecios()->sole()->user->is($admin));
    }

    public function test_rejects_a_negative_price(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $bloque = $empresa->productos()->sole();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ViewProducto::class, ['record' => $bloque->getRouteKey()])
            ->callAction('ajustarPrecio', data: ['precio_nuevo' => -5])
            ->assertHasFormErrors(['precio_nuevo']);

        $this->assertSame('0.00', $bloque->fresh()->precio_vigente);
    }

    public function test_encargado_sees_the_price_but_cannot_adjust_it(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();
        $bloque = $empresa->productos()->sole();

        $this->entrarComo($encargado, $empresa);

        Livewire::test(ListProductos::class)
            ->assertCanSeeTableRecords([$bloque])
            ->assertActionHidden(TestAction::make('ajustarPrecio')->table($bloque))
            ->assertActionHidden('create');
    }

    public function test_lists_only_the_products_of_the_open_empresa(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $ajeno = Empresa::factory()->create()->productos()->sole();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ListProductos::class)
            ->assertCanSeeTableRecords($empresa->productos)
            ->assertCanNotSeeTableRecords([$ajeno]);
    }
}
