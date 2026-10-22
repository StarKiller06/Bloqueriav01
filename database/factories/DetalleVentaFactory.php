<?php

namespace Database\Factories;

use App\Models\DetalleVenta;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetalleVenta>
 */
class DetalleVentaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venta_id' => Venta::factory(),
            'producto_id' => fn (array $attributes): int => Venta::find($attributes['venta_id'])->empresa->productos()->value('id'),
            'cantidad' => 100,
            'precio_unitario' => 1800,
            'subtotal' => 180000,
        ];
    }
}
