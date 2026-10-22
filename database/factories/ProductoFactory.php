<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => 'Bloque #'.fake()->unique()->numberBetween(3, 20),
            'precio_vigente' => fake()->numberBetween(15, 30) * 100,
        ];
    }
}
