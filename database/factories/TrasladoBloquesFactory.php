<?php

namespace Database\Factories;

use App\Models\Sucursal;
use App\Models\TrasladoBloques;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrasladoBloques>
 */
class TrasladoBloquesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sucursal_origen_id' => Sucursal::factory(),
            'empresa_id' => fn (array $attributes): int => Sucursal::find($attributes['sucursal_origen_id'])->empresa_id,
            'sucursal_destino_id' => fn (array $attributes): int => Sucursal::factory()->create(['empresa_id' => $attributes['empresa_id']])->id,
            'producto_id' => fn (array $attributes): int => Sucursal::find($attributes['sucursal_origen_id'])->empresa->productos()->value('id'),
            'cantidad' => 100,
            'fecha' => now()->toDateString(),
        ];
    }
}
