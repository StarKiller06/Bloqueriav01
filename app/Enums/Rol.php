<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Rol: string implements HasLabel
{
    case Administrador = 'administrador';
    case Encargado = 'encargado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Encargado => 'Encargado',
        };
    }
}
