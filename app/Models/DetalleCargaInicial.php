<?php

namespace App\Models;

use Database\Factories\DetalleCargaInicialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloques y cemento contados en una sucursal, y para cuántos bloques alcanzaba la arena que había.
 */
#[Table('detalles_carga_inicial')]
#[Fillable(['sucursal_id', 'bloques', 'cemento', 'arena_bloques'])]
class DetalleCargaInicial extends Model
{
    /** @use HasFactory<DetalleCargaInicialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bloques' => 'integer',
            'cemento' => 'integer',
            'arena_bloques' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CargaInicial, $this>
     */
    public function carga(): BelongsTo
    {
        return $this->belongsTo(CargaInicial::class, 'carga_inicial_id');
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
