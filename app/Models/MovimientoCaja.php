<?php

namespace App\Models;

use App\Enums\TipoMovimiento;
use Database\Factories\MovimientoCajaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Cada entrada o salida de dinero. El saldo de la caja nunca se guarda: es la suma de estos movimientos.
 * Los que vienen de otro módulo (una venta, un pago) apuntan a él con "origen".
 */
#[Table('movimientos_caja')]
#[Fillable(['sucursal_id', 'concepto_caja_id', 'user_id', 'tipo', 'monto', 'fecha', 'nota'])]
class MovimientoCaja extends Model
{
    /** @use HasFactory<MovimientoCajaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimiento::class,
            'monto' => 'decimal:2',
            'fecha' => 'date',
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
     * @return BelongsTo<ConceptoCaja, $this>
     */
    public function concepto(): BelongsTo
    {
        return $this->belongsTo(ConceptoCaja::class, 'concepto_caja_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function origen(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Creado a mano en el formulario, no por otro módulo.
     */
    public function esManual(): bool
    {
        return $this->origen_type === null && ! $this->concepto->esDelSistema();
    }

    public function montoConSigno(): float
    {
        return $this->tipo->signo() * (float) $this->monto;
    }
}
