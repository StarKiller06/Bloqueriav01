<?php

namespace App\Actions\Arranque;

use App\Exceptions\ReglaDeNegocio;
use App\Models\CargaInicial;

/**
 * Confirma que la carga inicial coincide con el papel. Desde aquí ya no se puede cambiar.
 */
class CerrarCargaInicial
{
    public function __invoke(CargaInicial $carga): void
    {
        if ($carga->estaCerrada()) {
            throw new ReglaDeNegocio('La carga inicial ya estaba cerrada.');
        }

        $carga->forceFill(['cerrada_at' => now()])->save();
    }
}
