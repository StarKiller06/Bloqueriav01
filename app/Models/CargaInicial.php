<?php

namespace App\Models;

use Database\Factories\CargaInicialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lo que había según el papel el día en que se empezó a usar el sistema.
 * Se corrige hasta que coincida con el papel; después se cierra y ya no cambia.
 */
#[Table('cargas_iniciales')]
#[Fillable(['fecha_corte', 'saldo_caja', 'user_id'])]
class CargaInicial extends Model
{
    /** @use HasFactory<CargaInicialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_corte' => 'date',
            'saldo_caja' => 'decimal:2',
            'cerrada_at' => 'datetime',
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
     * @return HasMany<DetalleCargaInicial, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCargaInicial::class);
    }

    public function estaCerrada(): bool
    {
        return $this->cerrada_at !== null;
    }
}
