<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum TipoMovimiento: string implements HasColor, HasIcon, HasLabel
{
    case Entrada = 'entrada';
    case Gasto = 'gasto';

    public function getLabel(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Gasto => 'Gasto',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Entrada => 'success',
            self::Gasto => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Entrada => Heroicon::OutlinedArrowDownTray,
            self::Gasto => Heroicon::OutlinedArrowUpTray,
        };
    }

    /**
     * +1 si suma a la caja, -1 si resta.
     */
    public function signo(): int
    {
        return $this === self::Entrada ? 1 : -1;
    }
}
