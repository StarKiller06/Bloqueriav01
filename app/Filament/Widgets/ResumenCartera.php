<?php

namespace App\Filament\Widgets;

use App\Enums\FormaPago;
use App\Models\Empresa;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * Lo que los clientes deben (cartera) y las ventas del mes.
 */
class ResumenCartera extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Ventas y cartera';

    protected function getStats(): array
    {
        /** @var Empresa $empresa */
        $empresa = Filament::getTenant();

        $porCobrar = $empresa->ventas()->porCobrar();
        $vencidas = $empresa->ventas()->vencidas();

        $delMes = $empresa->ventas()->vigentes()
            ->whereBetween('fecha', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->selectRaw('forma_pago, sum(total) as total, count(*) as cantidad')
            ->groupBy('forma_pago')
            ->get()
            ->keyBy(fn ($fila): string => $fila->forma_pago instanceof FormaPago ? $fila->forma_pago->value : $fila->forma_pago);

        $totalMes = (float) $delMes->sum('total');
        $credito = (float) ($delMes[FormaPago::Credito->value]->total ?? 0);
        $cantidadVencidas = (clone $vencidas)->count();

        return [
            Stat::make('Ventas de '.now()->translatedFormat('F'), $this->pesos($totalMes))
                ->description(sprintf('%d ventas · %s a crédito', (int) $delMes->sum('cantidad'), $this->pesos($credito)))
                ->descriptionIcon(Heroicon::OutlinedShoppingCart)
                ->color('primary'),
            Stat::make('Por cobrar', $this->pesos((float) (clone $porCobrar)->sum('saldo_pendiente')))
                ->description((clone $porCobrar)->count().' ventas a crédito pendientes')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning'),
            Stat::make('Vencido', $this->pesos((float) (clone $vencidas)->sum('saldo_pendiente')))
                ->description($cantidadVencidas === 0 ? 'Ningún crédito vencido' : "{$cantidadVencidas} créditos pasaron su fecha límite")
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($cantidadVencidas === 0 ? 'success' : 'danger'),
        ];
    }

    private function pesos(float $valor): string
    {
        return Number::currency($valor, 'COP', 'es_CO', 0);
    }
}
