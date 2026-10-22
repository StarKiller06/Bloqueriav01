<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Bloquería '.fake()->lastName(),
            'nit' => fake()->unique()->numerify('9########-#'),
            'bloques_por_bulto' => 100,
            'bloques_por_viaje' => 1200,
            'tarifa_por_bulto' => 15000,
        ];
    }

    public function conUsuario(User $user, Rol $rol = Rol::Administrador): static
    {
        return $this->afterCreating(fn (Empresa $empresa) => $empresa->agregarUsuario($user, $rol));
    }
}
