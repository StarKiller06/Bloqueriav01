<?php

namespace App\Models;

use App\Enums\ItemInventario;
use App\Enums\TipoMovimientoInventario;
use Database\Factories\MovimientoInventarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Cada entrada (+) o salida (−) de bloques o cemento en una sucursal.
 * El inventario nunca se guarda: es la suma de "cantidad".
 */
#[Table('movimientos_inventario')]
#[Fillable(['sucursal_id', 'item', 'producto_id', 'tipo', 'cantidad', 'fecha', 'user_id', 'nota'])]
class MovimientoInventario extends Model
{
    /** @use HasFactory<MovimientoInventarioFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item' => ItemInventario::class,
            'tipo' => TipoMovimientoInventario::class,
            'cantidad' => 'integer',
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
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
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
}
