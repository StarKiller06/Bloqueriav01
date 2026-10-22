<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Lo que se cuenta en el inventario. La arena no es inventario: se lleva por viajes.
 */
enum ItemInventario: string implements HasLabel
{
    case Bloque = 'bloque';
    case Cemento = 'cemento';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bloque => 'Bloques',
            self::Cemento => 'Cemento',
        };
    }

    public function unidad(): string
    {
        return match ($this) {
            self::Bloque => 'unidades',
            self::Cemento => 'bultos',
        };
    }
}
