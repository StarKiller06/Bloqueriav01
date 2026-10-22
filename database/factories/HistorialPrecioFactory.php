<?php

namespace Database\Factories;

use App\Models\HistorialPrecio;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistorialPrecio>
 */
class HistorialPrecioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'user_id' => null,
            'precio_anterior' => 1800,
            'precio_nuevo' => 2000,
        ];
    }
}
