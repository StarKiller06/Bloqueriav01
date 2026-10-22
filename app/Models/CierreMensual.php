<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CierreMensualFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un mes cerrado. Guarda el reporte tal como estaba al cerrar, para que siempre dé lo mismo.
 */
#[Table('cierres_mensuales')]
#[Fillable(['anio', 'mes', 'reporte', 'user_id'])]
class CierreMensual extends Model
{
    /** @use HasFactory<CierreMensualFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'mes' => 'integer',
            'reporte' => 'array',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inicio(): CarbonImmutable
    {
        return CarbonImmutable::create($this->anio, $this->mes, 1)->startOfMonth();
    }
}
