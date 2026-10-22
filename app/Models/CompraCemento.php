<?php

namespace App\Models;

use Database\Factories\CompraCementoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('compras_cemento')]
#[Fillable(['fecha', 'proveedor', 'bultos', 'precio_bulto', 'user_id'])]
class CompraCemento extends Model
{
    /** @use HasFactory<CompraCementoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'bultos' => 'integer',
            'precio_bulto' => 'decimal:2',
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
     * @return HasMany<RepartoCemento, $this>
     */
    public function repartos(): HasMany
    {
        return $this->hasMany(RepartoCemento::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function total(): float
    {
        return $this->bultos * (float) $this->precio_bulto;
    }
}
