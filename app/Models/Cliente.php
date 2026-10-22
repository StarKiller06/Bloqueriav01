<?php

namespace App\Models;

use App\Enums\TipoDocumento;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tipo_documento', 'documento', 'nombre', 'email', 'telefono', 'direccion'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_documento' => TipoDocumento::class,
        ];
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
