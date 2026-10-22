<?php

namespace Database\Factories;

use App\Models\Sucursal;
use App\Models\Trabajador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trabajador>
 */
class TrabajadorFactory extends Factory
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
            'nombre' => fake()->name(),
            'documento' => fake()->unique()->numerify('##########'),
            'telefono' => fake()->numerify('3#########'),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }
}
