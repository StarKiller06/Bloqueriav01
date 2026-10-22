<?php

namespace Tests\Feature\Filament\Resources\ConceptosCaja;

use App\Enums\ConceptoSistema;
use App\Enums\TipoMovimiento;
use App\Filament\Resources\ConceptosCaja\Pages\ManageConceptosCaja;
use App\Models\Empresa;
use App\Models\MovimientoCaja;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConceptoCajaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_new_manual_concepto(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageConceptosCaja::class)
            ->callAction('create', data: ['nombre' => 'Agua', 'tipo' => TipoMovimiento::Gasto->value])
            ->assertHasNoFormErrors();

        $this->assertFalse($empresa->conceptosCaja()->firstWhere('nombre', 'Agua')->esDelSistema());
    }

    public function test_rejects_a_repeated_name_in_the_same_empresa(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageConceptosCaja::class)
            ->callAction('create', data: ['nombre' => 'Luz', 'tipo' => TipoMovimiento::Gasto->value])
            ->assertHasFormErrors(['nombre']);
    }

    public function test_system_conceptos_cannot_be_edited_or_deleted(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $sistema = $empresa->conceptoSistema(ConceptoSistema::PagoTrabajador);

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageConceptosCaja::class)
            ->assertActionHidden(TestAction::make('edit')->table($sistema))
            ->assertActionHidden(TestAction::make('delete')->table($sistema));
    }

    public function test_a_concepto_with_movements_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        $usado = $empresa->conceptosCaja()->firstWhere('nombre', 'Luz');
        MovimientoCaja::factory()->for($usado, 'concepto')->create();
        $libre = $empresa->conceptosCaja()->firstWhere('nombre', 'Transporte');

        $this->entrarComo($admin, $empresa);

        Livewire::test(ManageConceptosCaja::class)
            ->assertActionHidden(TestAction::make('delete')->table($usado))
            ->assertActionVisible(TestAction::make('delete')->table($libre));
    }
}
