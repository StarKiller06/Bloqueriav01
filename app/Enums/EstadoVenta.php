<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoVenta: string implements HasColor, HasLabel
{
    case Pagada = 'pagada';
    case Pendiente = 'pendiente';
    case Vencida = 'vencida';
    case Anulada = 'anulada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pagada => 'Pagada',
            self::Pendiente => 'Pendiente',
            self::Vencida => 'Vencida',
            self::Anulada => 'Anulada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pagada => 'success',
            self::Pendiente => 'warning',
            self::Vencida => 'danger',
            self::Anulada => 'gray',
        };
    }
}
