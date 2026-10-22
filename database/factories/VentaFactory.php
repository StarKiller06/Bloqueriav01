<?php

namespace Database\Factories;

use App\Enums\FormaPago;
use App\Models\Sucursal;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Solo el registro. Para que también mueva inventario y caja, usa la acción RegistrarVenta.
 *
 * @extends Factory<Venta>
 */
class VentaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sucursal_id' => Sucursal::factory(),
            'empresa_id' => fn (array $attributes): int => Sucursal::find($attributes['sucursal_id'])->empresa_id,
            'numero' => fn (array $attributes): int => (int) Venta::where('empresa_id', $attributes['empresa_id'])->max('numero') + 1,
            'cliente_id' => null,
            'fecha' => now()->toDateString(),
            'forma_pago' => FormaPago::Contado,
            'total' => 180000,
            'saldo_pendiente' => 0,
        ];
    }

    public function credito(float $saldo, ?string $vence = null): static
    {
        return $this->state([
            'forma_pago' => FormaPago::Credito,
            'saldo_pendiente' => $saldo,
            'fecha_vencimiento' => $vence,
        ]);
    }
}
