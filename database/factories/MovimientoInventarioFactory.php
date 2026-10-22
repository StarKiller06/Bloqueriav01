<?php

namespace Database\Factories;

use App\Enums\ItemInventario;
use App\Enums\TipoMovimientoInventario;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoInventario>
 */
class MovimientoInventarioFactory extends Factory
{
    /**
     * Por defecto, una entrada de cemento por ajuste.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sucursal_id' => Sucursal::factory(),
            'empresa_id' => fn (array $attributes): int => Sucursal::find($attributes['sucursal_id'])->empresa_id,
            'item' => ItemInventario::Cemento,
            'producto_id' => null,
            'tipo' => TipoMovimientoInventario::Ajuste,
            'cantidad' => 10,
            'fecha' => now()->toDateString(),
        ];
    }

    /**
     * Bloques del producto "Bloque" de la empresa de la sucursal.
     */
    public function bloques(int $cantidad): static
    {
        return $this->state([
            'item' => ItemInventario::Bloque,
            'producto_id' => fn (array $attributes): int => Sucursal::find($attributes['sucursal_id'])->empresa->productos()->value('id'),
            'cantidad' => $cantidad,
        ]);
    }

    public function cemento(int $bultos): static
    {
        return $this->state(['item' => ItemInventario::Cemento, 'cantidad' => $bultos]);
    }
}
