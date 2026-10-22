<?php

namespace Tests\Feature\Filament\Resources\MovimientosInventario;

use App\Enums\ItemInventario;
use App\Enums\Rol;
use App\Filament\Resources\MovimientosInventario\Pages\ManageMovimientosInventario;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovimientoInventarioResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trasladar_moves_bloques_between_sucursales(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user, Rol::Encargado)->create();
        [$norte, $sur] = Sucursal::factory()->count(2)->for($empresa)->create();
        MovimientoInventario::factory()->for($norte)->bloques(500)->create();

        $this->entrarComo($user, $empresa);

        Livewire::test(ManageMovimientosInventario::class)
            ->callAction('trasladar', data: [
                'origen_id' => $norte->id,
                'destino_id' => $sur->id,
                'producto_id' => $empresa->productos()->sole()->id,
                'cantidad' => 200,
                'fecha' => now()->toDateString(),
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(300, $norte->stock(ItemInventario::Bloque));
        $this->assertSame(200, $sur->stock(ItemInventario::Bloque));
    }

    public function test_administrator_adjusts_cemento_with_a_reason(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        MovimientoInventario::factory()->for($sucursal)->cemento(10)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageMovimientosInventario::class)
            ->callAction('ajustar', data: [
                'sucursal_id' => $sucursal->id,
                'item' => ItemInventario::Cemento->value,
                'cantidad' => -2,
                'fecha' => now()->toDateString(),
                'nota' => 'Conteo físico',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(8, $sucursal->stock(ItemInventario::Cemento));
    }

    public function test_encargado_cannot_adjust(): void
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user, Rol::Encargado)->create();

        $this->entrarComo($user, $empresa);

        Livewire::test(ManageMovimientosInventario::class)
            ->assertActionHidden('ajustar')
            ->assertActionVisible('trasladar');
    }
}
