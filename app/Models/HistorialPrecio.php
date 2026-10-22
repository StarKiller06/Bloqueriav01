<?php

namespace App\Models;

use Database\Factories\HistorialPrecioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'precio_anterior', 'precio_nuevo'])]
class HistorialPrecio extends Model
{
    /** @use HasFactory<HistorialPrecioFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_anterior' => 'decimal:2',
            'precio_nuevo' => 'decimal:2',
        ];
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
}
