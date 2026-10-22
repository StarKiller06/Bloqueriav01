<?php

namespace App\Models;

use Database\Factories\TrabajadorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('trabajadores')]
#[Fillable(['sucursal_id', 'nombre', 'documento', 'telefono', 'activo'])]
class Trabajador extends Model
{
    /** @use HasFactory<TrabajadorFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
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
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    /**
     * @return HasMany<Produccion, $this>
     */
    public function producciones(): HasMany
    {
        return $this->hasMany(Produccion::class);
    }

    /**
     * @return HasMany<PagoTrabajador, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(PagoTrabajador::class);
    }

    /**
     * Cuántos bloques lleva por encima (+) o por debajo (−) del estándar en toda su historia.
     */
    public function rendimientoAcumulado(): int
    {
        return (int) $this->producciones()->sum('diferencia');
    }

    public function bultosPorPagar(): int
    {
        return (int) $this->producciones()->sinPagar()->sum('bultos_usados');
    }
}
