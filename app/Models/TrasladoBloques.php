<?php

namespace App\Models;

use Database\Factories\TrasladoBloquesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('traslados_bloques')]
#[Fillable(['producto_id', 'sucursal_origen_id', 'sucursal_destino_id', 'cantidad', 'fecha', 'nota', 'user_id'])]
class TrasladoBloques extends Model
{
    /** @use HasFactory<TrasladoBloquesFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad' => 'integer',
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
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function origen(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_origen_id');
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function destino(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_destino_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
