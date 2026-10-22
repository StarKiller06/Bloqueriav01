<?php

namespace Database\Factories;

use App\Models\CierreMensual;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CierreMensual>
 */
class CierreMensualFactory extends Factory
{
    /**
     * Por defecto, el mes pasado.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'anio' => now()->subMonthNoOverflow()->year,
            'mes' => now()->subMonthNoOverflow()->month,
            'reporte' => [],
        ];
    }
}
