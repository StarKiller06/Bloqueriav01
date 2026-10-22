<?php

namespace Database\Factories;

use App\Enums\TipoMovimiento;
use App\Models\ConceptoCaja;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConceptoCaja>
 */
class ConceptoCajaFactory extends Factory
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
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'tipo' => TipoMovimiento::Gasto,
            'clave' => null,
        ];
    }

    public function entrada(): static
    {
        return $this->state(['tipo' => TipoMovimiento::Entrada]);
    }

    public function gasto(): static
    {
        return $this->state(['tipo' => TipoMovimiento::Gasto]);
    }
}
