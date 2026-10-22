<?php

namespace Database\Factories;

use App\Models\Abono;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Abono>
 */
class AbonoFactory extends Factory
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
            'empresa_id' => fn (array $attributes): int => Venta::find($attributes['venta_id'])->empresa_id,
            'fecha' => now()->toDateString(),
            'monto' => 50000,
        ];
    }
}
