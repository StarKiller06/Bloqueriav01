<?php

namespace Database\Factories;

use App\Models\CargaInicial;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Solo el registro. Para que mueva caja e inventario, usa la acción GuardarCargaInicial.
 *
 * @extends Factory<CargaInicial>
 */
class CargaInicialFactory extends Factory
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
            'fecha_corte' => now()->startOfMonth()->toDateString(),
            'saldo_caja' => 1000000,
        ];
    }
}
