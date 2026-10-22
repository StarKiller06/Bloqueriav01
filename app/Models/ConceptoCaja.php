<?php

namespace App\Models;

use App\Enums\ConceptoSistema;
use App\Enums\TipoMovimiento;
use Database\Factories\ConceptoCajaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('conceptos_caja')]
#[Fillable(['nombre', 'tipo', 'clave'])]
class ConceptoCaja extends Model
{
    /** @use HasFactory<ConceptoCajaFactory> */
    use HasFactory;

    /**
     * Conceptos con los que arranca cada bloquería. Los manuales se pueden cambiar o borrar.
     *
     * @var array<string, TipoMovimiento>
     */
    public const array MANUALES_INICIALES = [
        'Venta de arena' => TipoMovimiento::Entrada,
        'Luz' => TipoMovimiento::Gasto,
        'Transporte' => TipoMovimiento::Gasto,
        'Reparaciones' => TipoMovimiento::Gasto,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimiento::class,
            'clave' => ConceptoSistema::class,
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
     * @return HasMany<MovimientoCaja, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    public function esDelSistema(): bool
    {
        return $this->clave !== null;
    }

    /**
     * Conceptos que el usuario puede elegir en el formulario de entradas y gastos.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function manuales(Builder $query): void
    {
        $query->whereNull('clave');
    }
}
