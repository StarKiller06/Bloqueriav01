<?php

namespace App\Filament\Pages;

use App\Actions\Reportes\CerrarMes;
use App\Actions\Reportes\ObtenerReporteMensual;
use App\Actions\Reportes\ReabrirMes;
use App\Filament\Support\Reglas;
use App\Models\CierreMensual;
use App\Models\Empresa;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * El reporte de cada mes: en pantalla, en PDF y en Excel. Aquí también se cierra el mes.
 */
class ReportesMensuales extends Page
{
    protected string $view = 'filament.pages.reportes-mensuales';

    protected static ?string $title = 'Reportes mensuales';

    protected static ?string $slug = 'reportes';

    protected static string|UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    #[Url]
    public ?string $periodo = null;

    public function mount(): void
    {
        $this->periodo ??= array_key_first($this->meses());
        $this->form->fill(['periodo' => $this->periodo]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('periodo')
                ->label('Mes')
                ->options(fn (): array => $this->meses())
                ->selectablePlaceholder(false)
                ->live()
                ->afterStateUpdated(function (?string $state): void {
                    $this->periodo = $state;
                    unset($this->reporte, $this->cierre);
                })
                ->extraFieldWrapperAttributes(['style' => 'max-width: 20rem']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function reporte(): array
    {
        return app(ObtenerReporteMensual::class)($this->empresa(), ...$this->anioMes());
    }

    #[Computed]
    public function cierre(): ?CierreMensual
    {
        [$anio, $mes] = $this->anioMes();

        return $this->empresa()->cierresMensuales()->with('user')->where('anio', $anio)->where('mes', $mes)->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->url(fn (): string => $this->urlDescarga('pdf')),
            Action::make('excel')
                ->label('Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->url(fn (): string => $this->urlDescarga('xlsx')),
            Action::make('cerrar')
                ->label('Cerrar mes')
                ->icon(Heroicon::OutlinedLockClosed)
                ->visible(fn (): bool => auth()->user()->esAdministrador()
                    && $this->cierre === null
                    && CarbonImmutable::create(...$this->anioMes())->endOfMonth()->isPast())
                ->requiresConfirmation()
                ->modalHeading(fn (): string => 'Cerrar '.mb_strtolower($this->reporte['periodo']['nombre']))
                ->modalDescription('Revisa que caja e inventario coincidan con la realidad. Después de cerrar, nadie podrá registrar ni corregir movimientos con fecha de este mes, y el reporte quedará guardado tal como está.')
                ->action(fn (Action $action, CerrarMes $cerrar) => Reglas::aplicar($action, function () use ($cerrar): void {
                    $cerrar($this->empresa(), ...$this->anioMes(), user: auth()->user());
                    unset($this->reporte, $this->cierre);
                }))
                ->successNotificationTitle('Mes cerrado'),
            Action::make('reabrir')
                ->label('Reabrir mes')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('danger')
                ->visible(fn (): bool => auth()->user()->esAdministrador() && $this->cierre !== null)
                ->requiresConfirmation()
                ->modalDescription('Solo se puede reabrir el último mes cerrado. Al volver a cerrarlo, el reporte se calcula de nuevo.')
                ->action(fn (Action $action, ReabrirMes $reabrir) => Reglas::aplicar($action, function () use ($reabrir): void {
                    $reabrir($this->cierre);
                    unset($this->reporte, $this->cierre);
                }))
                ->successNotificationTitle('Mes reabierto'),
        ];
    }

    /**
     * Desde el primer mes con movimientos hasta el actual, el más reciente primero.
     *
     * @return array<string, string>
     */
    private function meses(): array
    {
        $empresa = $this->empresa();
        $primera = collect([
            $empresa->movimientosCaja()->min('fecha'),
            $empresa->movimientosInventario()->min('fecha'),
        ])->filter()->min();

        $desde = $primera ? CarbonImmutable::parse($primera)->startOfMonth() : now()->toImmutable()->startOfMonth();
        $cerrados = $empresa->cierresMensuales()->get()->map(fn (CierreMensual $cierre): string => sprintf('%04d-%02d', $cierre->anio, $cierre->mes))->all();

        $meses = [];

        for ($mes = now()->toImmutable()->startOfMonth(); $mes->gte($desde); $mes = $mes->subMonth()) {
            $clave = $mes->format('Y-m');
            $meses[$clave] = ucfirst($mes->translatedFormat('F \d\e Y')).(in_array($clave, $cerrados, true) ? ' · cerrado' : '');
        }

        return $meses;
    }

    /**
     * @return array{int, int}
     */
    private function anioMes(): array
    {
        [$anio, $mes] = array_map('intval', explode('-', $this->periodo ?? now()->format('Y-m')));

        return [$anio, $mes];
    }

    private function urlDescarga(string $formato): string
    {
        [$anio, $mes] = $this->anioMes();

        return route('reportes.mensual', ['empresa' => $this->empresa(), 'anio' => $anio, 'mes' => $mes, 'formato' => $formato]);
    }

    private function empresa(): Empresa
    {
        /** @var Empresa */
        return Filament::getTenant();
    }
}
