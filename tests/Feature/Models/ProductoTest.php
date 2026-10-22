<?php

namespace Tests\Feature\Models;

use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_empresa_starts_with_its_bloque_at_zero(): void
    {
        $empresa = Empresa::factory()->create();

        $bloque = $empresa->productos()->sole();

        $this->assertSame('Bloque', $bloque->nombre);
        $this->assertSame('0.00', $bloque->precio_vigente);
    }

    public function test_changing_the_price_records_who_changed_it_and_the_previous_value(): void
    {
        $user = User::factory()->create();
        $producto = Producto::factory()->create(['precio_vigente' => 1800]);

        $producto->cambiarPrecio(2000, $user);

        $this->assertSame('2000.00', $producto->fresh()->precio_vigente);
        $cambio = $producto->historialPrecios()->sole();
        $this->assertSame('1800.00', $cambio->precio_anterior);
        $this->assertSame('2000.00', $cambio->precio_nuevo);
        $this->assertTrue($cambio->user->is($user));
    }

    public function test_setting_the_same_price_does_not_add_to_the_history(): void
    {
        $producto = Producto::factory()->create(['precio_vigente' => 1800]);

        $producto->cambiarPrecio('1800');

        $this->assertSame(0, $producto->historialPrecios()->count());
    }
}
