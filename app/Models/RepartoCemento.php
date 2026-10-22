<?php

namespace App\Models;

use Database\Factories\RepartoCementoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('repartos_cemento')]
#[Fillable(['sucursal_id', 'bultos'])]
class RepartoCemento extends Model
{
    /** @use HasFactory<RepartoCementoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bultos' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CompraCemento, $this>
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(CompraCemento::class, 'compra_cemento_id');
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
