<?php

namespace Database\Factories;

use App\Models\CargaInicial;
use App\Models\DetalleCargaInicial;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetalleCargaInicial>
 */
class DetalleCargaInicialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'carga_inicial_id' => CargaInicial::factory(),
            'sucursal_id' => fn (array $attributes): int => Sucursal::factory()->create([
                'empresa_id' => CargaInicial::find($attributes['carga_inicial_id'])->empresa_id,
            ])->id,
            'bloques' => 500,
            'cemento' => 10,
            'arena_bloques' => 600,
        ];
    }
}
