<?php

namespace Database\Factories;

use App\Models\CompraCemento;
use App\Models\RepartoCemento;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepartoCemento>
 */
class RepartoCementoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'compra_cemento_id' => CompraCemento::factory(),
            'sucursal_id' => fn (array $attributes): int => Sucursal::factory()->create([
                'empresa_id' => CompraCemento::find($attributes['compra_cemento_id'])->empresa_id,
            ])->id,
            'bultos' => 10,
        ];
    }
}
