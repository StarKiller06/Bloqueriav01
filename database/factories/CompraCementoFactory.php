<?php

namespace Database\Factories;

use App\Models\CompraCemento;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompraCemento>
 */
class CompraCementoFactory extends Factory
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
            'fecha' => now()->toDateString(),
            'proveedor' => fake()->company(),
            'bultos' => 20,
            'precio_bulto' => 32000,
        ];
    }
}
