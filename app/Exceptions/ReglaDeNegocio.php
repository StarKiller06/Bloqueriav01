<?php

namespace App\Exceptions;

use DomainException;

/**
 * Una operación que el negocio no permite, por ejemplo vender más bloques de los que hay.
 * El mensaje está escrito para mostrárselo al usuario tal cual.
 */
class ReglaDeNegocio extends DomainException
{
    //
}
