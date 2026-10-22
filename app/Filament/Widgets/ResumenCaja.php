<?php

namespace App\Filament\Widgets;

use App\Enums\TipoMovimiento;
use App\Models\Empresa;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ResumenCaja extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Caja';

    protected function getStats(): array
    {
        /** @var Empresa $empresa */
        $empresa = Filament::getTenant();

        $saldo = $empresa->saldoCaja();

        $delMes = $empresa->movimientosCaja()
            ->whereBetween('fecha', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->selectRaw('tipo, sum(monto) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        $mes = now()->translatedFormat('F');

        return [
            Stat::make('Saldo actual', $this->pesos($saldo))
                ->color($saldo < 0 ? 'danger' : 'primary')
                ->description($saldo < 0 ? 'La caja está en negativo' : 'Entradas menos gastos')
                ->descriptionIcon(Heroicon::OutlinedBanknotes),
            Stat::make("Entradas de {$mes}", $this->pesos((float) ($delMes[TipoMovimiento::Entrada->value] ?? 0)))
                ->color('success')
                ->descriptionIcon(TipoMovimiento::Entrada->getIcon())
                ->description('Suman a la caja'),
            Stat::make("Gastos de {$mes}", $this->pesos((float) ($delMes[TipoMovimiento::Gasto->value] ?? 0)))
                ->color('danger')
                ->descriptionIcon(TipoMovimiento::Gasto->getIcon())
                ->description('Restan de la caja'),
        ];
    }

    private function pesos(float $valor): string
    {
        return Number::currency($valor, 'COP', 'es_CO', 0);
    }
}
