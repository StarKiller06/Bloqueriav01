<?php

namespace Database\Factories;

use App\Enums\TipoDocumento;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
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
            'tipo_documento' => TipoDocumento::Cedula,
            'documento' => fake()->unique()->numerify('##########'),
            'nombre' => fake()->name(),
            'email' => fake()->safeEmail(),
            'telefono' => fake()->numerify('3#########'),
            'direccion' => fake()->streetAddress(),
        ];
    }
}
