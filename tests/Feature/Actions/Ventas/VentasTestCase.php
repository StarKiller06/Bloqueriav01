<?php

namespace Tests\Feature\Actions\Ventas;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Una bloquería con bloques en el patio y el bloque a $1.800.
 */
abstract class VentasTestCase extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected Sucursal $sucursal;

    protected Producto $bloque;

    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::factory()->create();
        $this->sucursal = Sucursal::factory()->for($this->empresa)->create();
        $this->bloque = $this->empresa->productos()->sole();
        $this->bloque->update(['precio_vigente' => 1800]);
        $this->cliente = Cliente::factory()->for($this->empresa)->create();
        MovimientoInventario::factory()->for($this->sucursal)->bloques(1000)->create();
    }

    /**
     * @return list<array{producto: Producto, cantidad: int, precio: float}>
     */
    protected function lineas(int $cantidad, float $precio = 1800): array
    {
        return [['producto' => $this->bloque, 'cantidad' => $cantidad, 'precio' => $precio]];
    }
}
