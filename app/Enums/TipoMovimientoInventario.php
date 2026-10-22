<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoMovimientoInventario: string implements HasColor, HasLabel
{
    case Apertura = 'apertura';
    case Produccion = 'produccion';
    case ConsumoCemento = 'consumo_cemento';
    case RepartoCemento = 'reparto_cemento';
    case Venta = 'venta';
    case Traslado = 'traslado';
    case Ajuste = 'ajuste';

    public function getLabel(): string
    {
        return match ($this) {
            self::Apertura => 'Apertura',
            self::Produccion => 'Producción',
            self::ConsumoCemento => 'Consumo en producción',
            self::RepartoCemento => 'Reparto de compra',
            self::Venta => 'Venta',
            self::Traslado => 'Traslado',
            self::Ajuste => 'Ajuste',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Produccion, self::RepartoCemento, self::Apertura => 'success',
            self::ConsumoCemento, self::Venta => 'danger',
            self::Traslado => 'info',
            self::Ajuste => 'warning',
        };
    }
}
