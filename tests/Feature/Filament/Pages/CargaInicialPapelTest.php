<?php

namespace Tests\Feature\Filament\Pages;

use App\Enums\ItemInventario;
use App\Enums\Rol;
use App\Filament\Pages\CargaInicialPapel;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CargaInicialPapelTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_loads_the_paper_then_closes_it(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();

        $this->entrarComo($admin, $empresa);

        $pagina = Livewire::test(CargaInicialPapel::class)
            ->fillForm([
                'fecha_corte' => now()->startOfMonth()->toDateString(),
                'saldo_caja' => 3000000,
                'sucursales' => [['sucursal_id' => $sucursal->id, 'bloques' => 800, 'cemento' => 12, 'arena_bloques' => 500]],
            ])
            ->callAction('guardar')
            ->assertHasNoFormErrors()
            ->assertNotified('Carga guardada. Compárala con el papel.');

        $this->assertSame(3000000.0, $empresa->saldoCaja());
        $this->assertSame(800, $sucursal->stock(ItemInventario::Bloque));

        $pagina->callAction('cerrar');

        $this->assertTrue($empresa->cargaInicial->estaCerrada());

        Livewire::test(CargaInicialPapel::class)
            ->assertActionHidden('guardar')
            ->assertSchemaComponentStateSet('saldo_caja', '3000000.00', 'form');
    }

    public function test_encargado_cannot_open_it(): void
    {
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($encargado, Rol::Encargado)->create();

        $this->actingAs($encargado)
            ->get(CargaInicialPapel::getUrl(tenant: $empresa))
            ->assertForbidden();
    }
}
