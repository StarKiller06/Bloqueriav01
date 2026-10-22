<?php

namespace Database\Factories;

use App\Models\Produccion;
use App\Models\Trabajador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Solo el registro. Para que también mueva el inventario, usa la acción RegistrarProduccion.
 *
 * @extends Factory<Produccion>
 */
class ProduccionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trabajador_id' => Trabajador::factory(),
            'empresa_id' => fn (array $attributes): int => Trabajador::find($attributes['trabajador_id'])->empresa_id,
            'sucursal_id' => fn (array $attributes): int => Trabajador::find($attributes['trabajador_id'])->sucursal_id,
            'producto_id' => fn (array $attributes): int => Trabajador::find($attributes['trabajador_id'])->empresa->productos()->value('id'),
            'fecha' => now()->toDateString(),
            'bultos_usados' => 4,
            'bloques_sacados' => 412,
            'bloques_esperados' => 400,
            'diferencia' => 12,
        ];
    }

    public function resultado(int $bultos, int $sacados): static
    {
        return $this->state([
            'bultos_usados' => $bultos,
            'bloques_sacados' => $sacados,
            'bloques_esperados' => $bultos * 100,
            'diferencia' => $sacados - $bultos * 100,
        ]);
    }
}
