<?php

namespace Tests\Feature\Filament\Resources\Producciones;

use App\Actions\Produccion\RegistrarProduccion;
use App\Enums\ItemInventario;
use App\Enums\Rol;
use App\Filament\Resources\Producciones\Pages\ManageProducciones;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Produccion;
use App\Models\Sucursal;
use App\Models\Trabajador;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProduccionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_encargado_registers_the_days_production(): void
    {
        [$encargado, $empresa, $sucursal, $trabajador] = $this->escenario(Rol::Encargado, cemento: 10);

        Livewire::test(ManageProducciones::class)
            ->callAction('registrar', data: [
                'trabajador_id' => $trabajador->id,
                'producto_id' => $empresa->productos()->sole()->id,
                'fecha' => now()->toDateString(),
                'bultos_usados' => 4,
                'bloques_sacados' => 412,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Registrado: 12 bloques por encima del estándar');

        $this->assertSame(12, $empresa->producciones()->sole()->diferencia);
        $this->assertSame(412, $sucursal->stock(ItemInventario::Bloque));
    }

    public function test_shows_why_it_cannot_register_without_cemento(): void
    {
        [, $empresa, , $trabajador] = $this->escenario(Rol::Encargado, cemento: 2);

        Livewire::test(ManageProducciones::class)
            ->callAction('registrar', data: [
                'trabajador_id' => $trabajador->id,
                'producto_id' => $empresa->productos()->sole()->id,
                'fecha' => now()->toDateString(),
                'bultos_usados' => 4,
                'bloques_sacados' => 400,
            ])
            ->assertNotified('No se pudo guardar');

        $this->assertSame(0, Produccion::count());
    }

    public function test_administrator_annuls_an_unpaid_production(): void
    {
        [, $empresa, $sucursal, $trabajador] = $this->escenario(Rol::Administrador, cemento: 10);
        $produccion = app(RegistrarProduccion::class)($trabajador, $empresa->productos()->sole(), 4, 412, now());

        Livewire::test(ManageProducciones::class)
            ->callAction(TestAction::make('anular')->table($produccion));

        $this->assertModelMissing($produccion);
        $this->assertSame(10, $sucursal->stock(ItemInventario::Cemento));
    }

    public function test_encargado_cannot_annul(): void
    {
        [, $empresa, , $trabajador] = $this->escenario(Rol::Encargado, cemento: 10);
        $produccion = app(RegistrarProduccion::class)($trabajador, $empresa->productos()->sole(), 4, 412, now());

        Livewire::test(ManageProducciones::class)
            ->assertActionHidden(TestAction::make('anular')->table($produccion));
    }

    /**
     * @return array{User, Empresa, Sucursal, Trabajador}
     */
    private function escenario(Rol $rol, int $cemento): array
    {
        $user = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($user, $rol)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        MovimientoInventario::factory()->for($sucursal)->cemento($cemento)->create();
        $trabajador = Trabajador::factory()->for($sucursal)->create();

        $this->entrarComo($user, $empresa);

        return [$user, $empresa, $sucursal, $trabajador];
    }
}
