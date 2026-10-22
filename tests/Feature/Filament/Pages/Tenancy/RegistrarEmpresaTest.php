<?php

namespace Tests\Feature\Filament\Pages\Tenancy;

use App\Enums\Rol;
use App\Filament\Pages\Tenancy\RegistrarEmpresa;
use App\Models\Empresa;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrarEmpresaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_registering_a_bloqueria_makes_the_user_its_administrator(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(RegistrarEmpresa::class)
            ->fillForm(['nombre' => 'Bloquería El Ladrillo', 'nit' => '901555444-1'])
            ->call('register')
            ->assertHasNoFormErrors();

        $empresa = Empresa::firstWhere('nit', '901555444-1');

        $this->assertSame('Bloquería El Ladrillo', $empresa->nombre);
        $this->assertSame(100, $empresa->bloques_por_bulto);
        $this->assertSame(Rol::Administrador, $user->rolEn($empresa));
    }

    public function test_rejects_a_nit_that_is_already_registered(): void
    {
        Empresa::factory()->create(['nit' => '901555444-1']);
        $this->actingAs(User::factory()->create());

        Livewire::test(RegistrarEmpresa::class)
            ->fillForm(['nombre' => 'Otra bloquería', 'nit' => '901555444-1'])
            ->call('register')
            ->assertHasFormErrors(['nit' => 'unique']);

        $this->assertSame(1, Empresa::count());
    }
}
