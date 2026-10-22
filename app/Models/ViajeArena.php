<?php

namespace App\Models;

use Database\Factories\ViajeArenaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('viajes_arena')]
#[Fillable(['sucursal_id', 'fecha', 'metros_cubicos', 'costo', 'bloques_estimados', 'user_id'])]
class ViajeArena extends Model
{
    /** @use HasFactory<ViajeArenaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'metros_cubicos' => 'decimal:2',
            'costo' => 'decimal:2',
            'bloques_estimados' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
