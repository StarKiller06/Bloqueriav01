<?php

namespace Tests\Feature\Filament\Pages;

use App\Actions\Arranque\CerrarCargaInicial;
use App\Actions\Arranque\GuardarCargaInicial;
use App\Enums\Rol;
use App\Filament\Pages\ReportesMensuales;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReportesMensualesTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private function escenario(Rol $rol = Rol::Administrador): void
    {
        $this->travelTo(Carbon::parse('2026-11-05'));

        $user = User::factory()->create();
        $this->empresa = Empresa::factory()->conUsuario($user, $rol)->create();
        $sucursal = Sucursal::factory()->for($this->empresa)->create(['nombre' => 'Norte']);
        $carga = app(GuardarCargaInicial::class)($this->empresa, Carbon::create(2026, 10, 1), 750000, [$sucursal->id => ['bloques' => 640, 'cemento' => 5, 'arena_bloques' => 0]]);
        app(CerrarCargaInicial::class)($carga);

        $this->entrarComo($user, $this->empresa);
    }

    public function test_shows_the_report_of_the_chosen_month(): void
    {
        $this->escenario();

        Livewire::test(ReportesMensuales::class, ['periodo' => '2026-10'])
            ->assertSeeText('Saldo al terminar el mes')
            ->assertSeeText('Norte')
            ->assertSeeText('640')
            ->assertSeeText('Mes abierto');
    }

    public function test_administrator_closes_a_finished_month(): void
    {
        $this->escenario();

        Livewire::test(ReportesMensuales::class, ['periodo' => '2026-10'])
            ->callAction('cerrar')
            ->assertNotified('Mes cerrado')
            ->assertSeeText('Mes cerrado');

        $this->assertTrue($this->empresa->mesCerrado(Carbon::create(2026, 10, 1)));
    }

    public function test_the_current_month_cannot_be_closed_yet(): void
    {
        $this->escenario();

        Livewire::test(ReportesMensuales::class, ['periodo' => '2026-11'])
            ->assertActionHidden('cerrar');
    }

    public function test_encargado_sees_reports_but_cannot_close(): void
    {
        $this->escenario(Rol::Encargado);

        Livewire::test(ReportesMensuales::class, ['periodo' => '2026-10'])
            ->assertActionHidden('cerrar')
            ->assertActionVisible('pdf');
    }
}
