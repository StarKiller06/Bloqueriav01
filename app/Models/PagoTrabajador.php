<?php

namespace App\Models;

use Database\Factories\PagoTrabajadorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('pagos_trabajador')]
#[Fillable(['trabajador_id', 'fecha', 'bultos_pagados', 'tarifa', 'monto', 'user_id'])]
class PagoTrabajador extends Model
{
    /** @use HasFactory<PagoTrabajadorFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'bultos_pagados' => 'integer',
            'tarifa' => 'decimal:2',
            'monto' => 'decimal:2',
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
     * @return BelongsTo<Trabajador, $this>
     */
    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    /**
     * @return HasMany<Produccion, $this>
     */
    public function producciones(): HasMany
    {
        return $this->hasMany(Produccion::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
