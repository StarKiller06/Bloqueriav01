<?php

namespace Tests\Feature\Filament\Resources\ComprasCemento;

use App\Enums\ItemInventario;
use App\Filament\Resources\ComprasCemento\Pages\ManageComprasCemento;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompraCementoResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_a_compra_split_between_sucursales(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        [$norte, $sur] = Sucursal::factory()->count(2)->for($empresa)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageComprasCemento::class)
            ->callAction('registrar', data: [
                'fecha' => now()->toDateString(),
                'proveedor' => 'Cementos del Valle',
                'bultos' => 30,
                'precio_bulto' => 32000,
                'reparto' => [
                    ['sucursal_id' => $norte->id, 'bultos' => 20],
                    ['sucursal_id' => $sur->id, 'bultos' => 10],
                ],
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(20, $norte->stock(ItemInventario::Cemento));
        $this->assertSame(10, $sur->stock(ItemInventario::Cemento));
        $this->assertSame(-960000.0, $empresa->saldoCaja());
    }

    public function test_explains_when_the_reparto_does_not_add_up(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageComprasCemento::class)
            ->callAction('registrar', data: [
                'fecha' => now()->toDateString(),
                'bultos' => 30,
                'precio_bulto' => 32000,
                'reparto' => [['sucursal_id' => $sucursal->id, 'bultos' => 25]],
            ])
            ->assertNotified('No se pudo guardar');

        $this->assertSame(0, $empresa->comprasCemento()->count());
    }
}
