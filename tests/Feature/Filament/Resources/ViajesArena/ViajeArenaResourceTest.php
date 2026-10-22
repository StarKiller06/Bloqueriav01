<?php

namespace Tests\Feature\Filament\Resources\ViajesArena;

use App\Filament\Resources\ViajesArena\Pages\ManageViajesArena;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViajeArenaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_a_viaje_of_seven_cubic_meters(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageViajesArena::class)
            ->callAction('registrar', data: [
                'sucursal_id' => $sucursal->id,
                'fecha' => now()->toDateString(),
                'metros_cubicos' => 7,
                'costo' => 700000,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(1200, $sucursal->arenaRestante());
        $this->assertSame(-700000.0, $empresa->saldoCaja());
    }

    public function test_rejects_a_sucursal_of_another_empresa(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $ajena = Sucursal::factory()->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageViajesArena::class)
            ->callAction('registrar', data: [
                'sucursal_id' => $ajena->id,
                'fecha' => now()->toDateString(),
                'metros_cubicos' => 7,
                'costo' => 700000,
            ])
            ->assertHasFormErrors(['sucursal_id']);

        $this->assertSame(0, $ajena->viajesArena()->count());
    }
}
