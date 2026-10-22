<?php

namespace App\Models;

use App\Enums\ItemInventario;
use Database\Factories\SucursalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Laravel pluraliza en inglés ("sucursals"), así que se indica la tabla.
 */
#[Table('sucursales')]
#[Fillable(['nombre', 'direccion'])]
class Sucursal extends Model
{
    /** @use HasFactory<SucursalFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * @return HasMany<Trabajador, $this>
     */
    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class);
    }

    /**
     * @return HasMany<MovimientoInventario, $this>
     */
    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /**
     * @return HasMany<ViajeArena, $this>
     */
    public function viajesArena(): HasMany
    {
        return $this->hasMany(ViajeArena::class);
    }

    /**
     * @return HasMany<Produccion, $this>
     */
    public function producciones(): HasMany
    {
        return $this->hasMany(Produccion::class);
    }

    /**
     * Existencias actuales: la suma de todos los movimientos del ítem en esta sucursal.
     */
    public function stock(ItemInventario $item): int
    {
        return (int) $this->movimientosInventario()->where('item', $item)->sum('cantidad');
    }

    /**
     * Bloques que aún alcanzan con la arena recibida: lo estimado por los viajes menos lo producido.
     * Es una estimación; puede quedar negativa si la arena rindió más de lo esperado.
     */
    public function arenaRestante(): int
    {
        return (int) $this->viajesArena()->sum('bloques_estimados')
            - (int) $this->producciones()->sum('bloques_sacados');
    }
}
