<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\Rol;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordarCreditosTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_only_the_administrators_with_the_pending_and_overdue_totals(): void
    {
        $admin = User::factory()->create();
        $encargado = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->conUsuario($encargado, Rol::Encargado)->create();
        $sucursal = Sucursal::factory()->for($empresa)->create();
        $cliente = Cliente::factory()->for($empresa)->create();
        Venta::factory()->for($sucursal)->for($cliente)->credito(100000, now()->subDays(5)->toDateString())->create(['empresa_id' => $empresa->id]);
        Venta::factory()->for($sucursal)->for($cliente)->credito(50000)->create(['empresa_id' => $empresa->id]);

        $this->artisan('creditos:recordar')->assertSuccessful();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(0, $encargado->notifications()->count());
        $this->assertStringContainsString('2 ventas a crédito', $admin->notifications()->sole()->data['body']);
        $this->assertStringContainsString('1 ya pasaron', $admin->notifications()->sole()->data['body']);
    }

    public function test_does_not_notify_when_nobody_owes(): void
    {
        $admin = User::factory()->create();
        $empresa = Empresa::factory()->conUsuario($admin)->create();
        Venta::factory()->for(Sucursal::factory()->for($empresa))->create(['empresa_id' => $empresa->id]);

        $this->artisan('creditos:recordar')->assertSuccessful();

        $this->assertSame(0, $admin->notifications()->count());
    }
}
