<?php

namespace Database\Factories;

use App\Models\Sucursal;
use App\Models\ViajeArena;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ViajeArena>
 */
class ViajeArenaFactory extends Factory
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
            'fecha' => now()->toDateString(),
            'metros_cubicos' => 7,
            'costo' => 700000,
            'bloques_estimados' => 1200,
        ];
    }
}
