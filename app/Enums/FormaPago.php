<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum FormaPago: string implements HasColor, HasIcon, HasLabel
{
    case Contado = 'contado';
    case Credito = 'credito';

    public function getLabel(): string
    {
        return match ($this) {
            self::Contado => 'Contado',
            self::Credito => 'Crédito',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Contado => 'success',
            self::Credito => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Contado => Heroicon::OutlinedBanknotes,
            self::Credito => Heroicon::OutlinedClock,
        };
    }
}
