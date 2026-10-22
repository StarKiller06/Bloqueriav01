<?php

namespace App\Models;

use Database\Factories\ProduccionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Lo que sacó un trabajador en un día. El esperado se guarda con el estándar de ese día,
 * para que un cambio futuro del estándar no altere el rendimiento ya registrado.
 */
#[Table('producciones')]
#[Fillable(['sucursal_id', 'trabajador_id', 'producto_id', 'fecha', 'bultos_usados', 'bloques_sacados', 'bloques_esperados', 'diferencia', 'user_id'])]
class Produccion extends Model
{
    /** @use HasFactory<ProduccionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'bultos_usados' => 'integer',
            'bloques_sacados' => 'integer',
            'bloques_esperados' => 'integer',
            'diferencia' => 'integer',
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
     * @return BelongsTo<Trabajador, $this>
     */
    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * @return BelongsTo<PagoTrabajador, $this>
     */
    public function pago(): BelongsTo
    {
        return $this->belongsTo(PagoTrabajador::class, 'pago_trabajador_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphMany<MovimientoInventario, $this>
     */
    public function movimientosInventario(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'origen');
    }

    public function estaPagada(): bool
    {
        return $this->pago_trabajador_id !== null;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sinPagar(Builder $query): void
    {
        $query->whereNull('pago_trabajador_id');
    }
}
