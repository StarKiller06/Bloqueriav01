<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use DomainException;

/**
 * Una operación que el negocio no permite, por ejemplo vender más bloques de los que hay.
 * El mensaje está escrito para mostrárselo al usuario tal cual.
 */
class ReglaDeNegocio extends DomainException
{
    public static function mesCerrado(CarbonInterface $fecha): self
    {
        return new self(sprintf(
            '%s ya está cerrado: no se puede registrar ni cambiar nada con esa fecha.',
            ucfirst($fecha->translatedFormat('F \\d\\e Y')),
        ));
    }
}
