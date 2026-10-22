<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Tipos de documento que acepta la DIAN para facturar.
 */
enum TipoDocumento: string implements HasLabel
{
    case Cedula = 'CC';
    case Nit = 'NIT';
    case CedulaExtranjeria = 'CE';
    case Pasaporte = 'PP';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cedula => 'Cédula de ciudadanía',
            self::Nit => 'NIT',
            self::CedulaExtranjeria => 'Cédula de extranjería',
            self::Pasaporte => 'Pasaporte',
        };
    }
}
