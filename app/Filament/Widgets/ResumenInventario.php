<?php

namespace App\Filament\Widgets;

use App\Enums\ItemInventario;
use App\Models\Empresa;
use App\Models\Sucursal;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * Bloques, cemento y arena de cada sucursal, más el total de la empresa.
 */
class ResumenInventario extends StatsOverviewWidget
{
    /**
     * Por debajo de esto se avisa que hay que pedir otro viaje de arena.
     */
    public const int ALERTA_ARENA = 200;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Inventario';

    protected function getStats(): array
    {
        /** @var Empresa $empresa */
        $empresa = Filament::getTenant();

        $stats = $empresa->sucursales()->orderBy('nombre')->get()
            ->map(fn (Sucursal $sucursal): Stat => $this->sucursal($sucursal))
            ->all();

        $stats[] = Stat::make('General', $this->unidades($empresa->stockGeneral(ItemInventario::Bloque)).' bloques')
            ->description($this->unidades($empresa->stockGeneral(ItemInventario::Cemento)).' bultos de cemento')
            ->descriptionIcon(Heroicon::OutlinedBuildingOffice2)
            ->color('primary');

        return $stats;
    }

    private function sucursal(Sucursal $sucursal): Stat
    {
        $cemento = $sucursal->stock(ItemInventario::Cemento);
        $arena = $sucursal->arenaRestante();
        $alerta = $cemento === 0 || $arena < self::ALERTA_ARENA;

        $avisos = array_filter([
            $cemento === 0 ? 'Sin cemento' : null,
            $arena < self::ALERTA_ARENA ? 'Pedir arena' : null,
        ]);

        return Stat::make($sucursal->nombre, $this->unidades($sucursal->stock(ItemInventario::Bloque)).' bloques')
            ->description(sprintf(
                '%s bultos · arena para ~%s bloques%s',
                $this->unidades($cemento),
                $this->unidades(max($arena, 0)),
                $avisos ? ' · '.implode(', ', $avisos) : '',
            ))
            ->descriptionIcon($alerta ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCube)
            ->color($alerta ? 'warning' : 'gray');
    }

    private function unidades(int $cantidad): string
    {
        return Number::format($cantidad, locale: 'es_CO');
    }
}
