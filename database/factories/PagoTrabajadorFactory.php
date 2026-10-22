<?php

namespace Database\Factories;

use App\Models\PagoTrabajador;
use App\Models\Trabajador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PagoTrabajador>
 */
class PagoTrabajadorFactory extends Factory
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
            'fecha' => now()->toDateString(),
            'bultos_pagados' => 4,
            'tarifa' => 15000,
            'monto' => 60000,
        ];
    }
}
