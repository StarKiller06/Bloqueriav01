<?php

namespace App\Models;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['nombre', 'precio_vigente'])]
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_vigente' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * @return HasMany<HistorialPrecio, $this>
     */
    public function historialPrecios(): HasMany
    {
        return $this->hasMany(HistorialPrecio::class)->latest()->latest('id');
    }

    /**
     * Cambia el precio vigente y deja constancia del cambio en el historial.
     * Las ventas ya hechas no se tocan: cada una guarda su propio precio.
     */
    public function cambiarPrecio(string|float $precioNuevo, ?User $user = null): void
    {
        $precioNuevo = number_format((float) $precioNuevo, 2, '.', '');

        if ($precioNuevo === $this->precio_vigente) {
            return;
        }

        DB::transaction(function () use ($precioNuevo, $user): void {
            $this->historialPrecios()->create([
                'user_id' => $user?->getKey(),
                'precio_anterior' => $this->precio_vigente,
                'precio_nuevo' => $precioNuevo,
            ]);

            $this->update(['precio_vigente' => $precioNuevo]);
        });
    }
}
