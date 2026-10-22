<?php

namespace Database\Factories;

use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoCaja>
 */
class MovimientoCajaFactory extends Factory
{
    /**
     * El tipo y la empresa salen del concepto, igual que en RegistrarMovimiento.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'concepto_caja_id' => ConceptoCaja::factory(),
            'empresa_id' => fn (array $attributes): int => ConceptoCaja::find($attributes['concepto_caja_id'])->empresa_id,
            'tipo' => fn (array $attributes) => ConceptoCaja::find($attributes['concepto_caja_id'])->tipo,
            'monto' => fake()->numberBetween(1, 100) * 1000,
            'fecha' => now()->toDateString(),
            'nota' => null,
        ];
    }
}
