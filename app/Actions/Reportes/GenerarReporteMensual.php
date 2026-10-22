<?php

namespace App\Actions\Reportes;

use App\Enums\FormaPago;
use App\Enums\ItemInventario;
use App\Enums\TipoMovimiento;
use App\Enums\TipoMovimientoInventario;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Trabajador;
use App\Models\Venta;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Arma el reporte de un mes con números simples (sin modelos), para poder guardarlo
 * tal cual al cerrar el mes y mostrarlo igual en pantalla, en PDF y en Excel.
 */
class GenerarReporteMensual
{
    private Empresa $empresa;

    private CarbonImmutable $desde;

    private CarbonImmutable $hasta;

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Empresa $empresa, int $anio, int $mes): array
    {
        $this->empresa = $empresa;
        $this->desde = CarbonImmutable::create($anio, $mes, 1)->startOfDay();
        $this->hasta = $this->desde->endOfMonth();

        $sucursales = $empresa->sucursales()->orderBy('nombre')->get();

        return [
            'empresa' => ['nombre' => $empresa->nombre, 'nit' => $empresa->nit],
            'periodo' => [
                'anio' => $anio,
                'mes' => $mes,
                'nombre' => ucfirst($this->desde->translatedFormat('F \d\e Y')),
                'desde' => $this->desde->toDateString(),
                'hasta' => $this->hasta->toDateString(),
            ],
            'generado_en' => now()->toIso8601String(),
            'caja' => $this->caja(),
            'inventario' => $this->inventario($sucursales),
            'arena' => $sucursales->map(fn (Sucursal $sucursal): array => $this->arena($sucursal))->all(),
            'trabajadores' => $this->trabajadores(),
            'ventas' => $this->ventas($sucursales),
            'costo_bloque' => $this->costoBloque(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function caja(): array
    {
        $movimientos = $this->empresa->movimientosCaja();

        $saldoInicial = (float) (clone $movimientos)->whereDate('fecha', '<', $this->desde)
            ->selectRaw('sum(case when tipo = ? then monto else -monto end) as saldo', [TipoMovimiento::Entrada->value])
            ->value('saldo');

        $porConcepto = (clone $movimientos)
            ->join('conceptos_caja', 'conceptos_caja.id', '=', 'movimientos_caja.concepto_caja_id')
            ->whereBetween('movimientos_caja.fecha', [$this->desde->toDateString(), $this->hasta->toDateString()])
            ->groupBy('movimientos_caja.tipo', 'conceptos_caja.nombre')
            ->orderBy('conceptos_caja.nombre')
            ->selectRaw('movimientos_caja.tipo as tipo, conceptos_caja.nombre as concepto, sum(movimientos_caja.monto) as total')
            ->toBase()
            ->get();

        $lista = fn (TipoMovimiento $tipo): array => $porConcepto
            ->where('tipo', $tipo->value)
            ->map(fn (object $fila): array => ['concepto' => $fila->concepto, 'total' => (float) $fila->total])
            ->values()
            ->all();

        $entradas = $lista(TipoMovimiento::Entrada);
        $gastos = $lista(TipoMovimiento::Gasto);
        $totalEntradas = array_sum(array_column($entradas, 'total'));
        $totalGastos = array_sum(array_column($gastos, 'total'));

        return [
            'saldo_inicial' => $saldoInicial,
            'entradas' => $entradas,
            'gastos' => $gastos,
            'total_entradas' => $totalEntradas,
            'total_gastos' => $totalGastos,
            'saldo_final' => $saldoInicial + $totalEntradas - $totalGastos,
        ];
    }

    /**
     * Por sucursal y general: con cuánto empezó el mes, qué entró y salió por cada motivo, y con cuánto terminó.
     *
     * @param  Collection<int, Sucursal>  $sucursales
     * @return array<string, mixed>
     */
    private function inventario(Collection $sucursales): array
    {
        $porSucursal = $sucursales->map(fn (Sucursal $sucursal): array => [
            'sucursal' => $sucursal->nombre,
            'bloques' => $this->kardex($sucursal, ItemInventario::Bloque),
            'cemento' => $this->kardex($sucursal, ItemInventario::Cemento),
        ]);

        $sumar = fn (string $item): array => collect($porSucursal->first()[$item] ?? [])
            ->keys()
            ->mapWithKeys(fn (string $columna): array => [$columna => $porSucursal->sum(fn (array $fila): int => $fila[$item][$columna])])
            ->all();

        return [
            'sucursales' => $porSucursal->all(),
            'general' => ['bloques' => $sumar('bloques'), 'cemento' => $sumar('cemento')],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function kardex(Sucursal $sucursal, ItemInventario $item): array
    {
        $movimientos = $sucursal->movimientosInventario()->where('item', $item);

        $inicial = (int) (clone $movimientos)->whereDate('fecha', '<', $this->desde)->sum('cantidad');

        $delMes = (clone $movimientos)
            ->whereBetween('fecha', [$this->desde->toDateString(), $this->hasta->toDateString()])
            ->groupBy('tipo')
            ->selectRaw('tipo, sum(cantidad) as total')
            ->toBase()
            ->pluck('total', 'tipo')
            ->map(fn ($total): int => (int) $total);

        $columnas = match ($item) {
            ItemInventario::Bloque => [
                'apertura' => [TipoMovimientoInventario::Apertura],
                'produccion' => [TipoMovimientoInventario::Produccion],
                'ventas' => [TipoMovimientoInventario::Venta],
                'traslados' => [TipoMovimientoInventario::Traslado],
                'ajustes' => [TipoMovimientoInventario::Ajuste],
            ],
            ItemInventario::Cemento => [
                'apertura' => [TipoMovimientoInventario::Apertura],
                'compras' => [TipoMovimientoInventario::RepartoCemento],
                'consumo' => [TipoMovimientoInventario::ConsumoCemento],
                'ajustes' => [TipoMovimientoInventario::Ajuste],
            ],
        };

        $fila = ['inicial' => $inicial];

        foreach ($columnas as $columna => $tipos) {
            $fila[$columna] = collect($tipos)->sum(fn (TipoMovimientoInventario $tipo): int => $delMes[$tipo->value] ?? 0);
        }

        $fila['final'] = $inicial + $delMes->sum();

        return $fila;
    }

    /**
     * @return array<string, mixed>
     */
    private function arena(Sucursal $sucursal): array
    {
        $viajes = $sucursal->viajesArena()->whereBetween('fecha', [$this->desde->toDateString(), $this->hasta->toDateString()]);

        return [
            'sucursal' => $sucursal->nombre,
            'viajes' => (clone $viajes)->count(),
            'costo' => (float) (clone $viajes)->sum('costo'),
            'bloques_estimados' => (int) (clone $viajes)->sum('bloques_estimados'),
            'bloques_producidos' => (int) $sucursal->producciones()
                ->whereBetween('fecha', [$this->desde->toDateString(), $this->hasta->toDateString()])
                ->sum('bloques_sacados'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function trabajadores(): array
    {
        $rango = [$this->desde->toDateString(), $this->hasta->toDateString()];

        return $this->empresa->trabajadores()
            ->with('sucursal')
            ->withSum(['producciones as bultos' => fn ($query) => $query->whereBetween('fecha', $rango)], 'bultos_usados')
            ->withSum(['producciones as sacados' => fn ($query) => $query->whereBetween('fecha', $rango)], 'bloques_sacados')
            ->withSum(['producciones as diferencia' => fn ($query) => $query->whereBetween('fecha', $rango)], 'diferencia')
            ->withSum(['pagos as pagado' => fn ($query) => $query->whereBetween('fecha', $rango)], 'monto')
            ->orderBy('nombre')
            ->get()
            ->filter(fn (Trabajador $trabajador): bool => $trabajador->bultos > 0 || $trabajador->pagado > 0)
            ->map(fn (Trabajador $trabajador): array => [
                'nombre' => $trabajador->nombre,
                'sucursal' => $trabajador->sucursal->nombre,
                'bultos' => (int) $trabajador->bultos,
                'sacados' => (int) $trabajador->sacados,
                'diferencia' => (int) $trabajador->diferencia,
                'pagado' => (float) $trabajador->pagado,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Sucursal>  $sucursales
     * @return array<string, mixed>
     */
    private function ventas(Collection $sucursales): array
    {
        $delMes = $this->empresa->ventas()->vigentes()
            ->whereBetween('fecha', [$this->desde->toDateString(), $this->hasta->toDateString()])
            ->with('detalles')
            ->get();

        $porSucursal = $sucursales->map(function (Sucursal $sucursal) use ($delMes): array {
            $ventas = $delMes->where('sucursal_id', $sucursal->id);

            return [
                'sucursal' => $sucursal->nombre,
                'cantidad' => $ventas->count(),
                'bloques' => (int) $ventas->sum(fn (Venta $venta): int => $venta->detalles->sum('cantidad')),
                'contado' => (float) $ventas->where('forma_pago', FormaPago::Contado)->sum('total'),
                'credito' => (float) $ventas->where('forma_pago', FormaPago::Credito)->sum('total'),
            ];
        });

        return [
            'por_sucursal' => $porSucursal->all(),
            'total_contado' => $porSucursal->sum('contado'),
            'total_credito' => $porSucursal->sum('credito'),
            'abonos' => (float) $this->empresa->ventas()->join('abonos', 'abonos.venta_id', '=', 'ventas.id')
                ->whereBetween('abonos.fecha', [$this->desde->toDateString(), $this->hasta->toDateString()])
                ->sum('abonos.monto'),
            'anuladas' => $this->empresa->ventas()->whereBetween('anulada_at', [$this->desde, $this->hasta])->count(),
            'cartera' => $this->cartera(),
        ];
    }

    /**
     * Lo que cada cliente debía al terminar el mes (no hoy): ventas a crédito hasta esa fecha
     * menos los abonos hasta esa fecha, sin las que estaban anuladas.
     *
     * @return list<array{cliente: string, saldo: float}>
     */
    private function cartera(): array
    {
        return $this->empresa->ventas()
            ->where('forma_pago', FormaPago::Credito)
            ->whereDate('fecha', '<=', $this->hasta)
            ->where(fn ($query) => $query->whereNull('anulada_at')->orWhere('anulada_at', '>', $this->hasta))
            ->withSum(['abonos as abonado' => fn ($query) => $query->whereDate('fecha', '<=', $this->hasta)], 'monto')
            ->with('cliente')
            ->get()
            ->groupBy(fn (Venta $venta): string => $venta->nombreCliente())
            ->map(fn (Collection $ventas, string $cliente): array => [
                'cliente' => $cliente,
                'saldo' => (float) $ventas->sum(fn (Venta $venta): float => (float) $venta->total - (float) $venta->abonado),
            ])
            ->filter(fn (array $fila): bool => $fila['saldo'] > 0)
            ->sortByDesc('saldo')
            ->values()
            ->all();
    }

    /**
     * Cuánto costó producir cada bloque en el mes, con el precio promedio pagado por el cemento
     * y la arena hasta ese mes y la tarifa por bulto. Es una estimación.
     *
     * @return array<string, float>|null
     */
    private function costoBloque(): ?array
    {
        $producciones = $this->empresa->producciones()->whereBetween('fecha', [$this->desde->toDateString(), $this->hasta->toDateString()]);
        $bultos = (int) (clone $producciones)->sum('bultos_usados');
        $sacados = (int) (clone $producciones)->sum('bloques_sacados');

        if ($sacados === 0) {
            return null;
        }

        $compras = $this->empresa->comprasCemento()->whereDate('fecha', '<=', $this->hasta);
        $bultosComprados = (int) (clone $compras)->sum('bultos');
        $precioBulto = $bultosComprados > 0 ? (float) (clone $compras)->selectRaw('sum(bultos * precio_bulto) as total')->value('total') / $bultosComprados : 0.0;

        $viajes = $this->empresa->viajesArena()->whereDate('fecha', '<=', $this->hasta)->where('costo', '>', 0);
        $bloquesArena = (int) (clone $viajes)->sum('bloques_estimados');
        $arenaPorBloque = $bloquesArena > 0 ? (float) (clone $viajes)->sum('costo') / $bloquesArena : 0.0;

        $cemento = $bultos * $precioBulto / $sacados;
        $manoDeObra = $bultos * (float) $this->empresa->tarifa_por_bulto / $sacados;

        $vendidos = $this->empresa->ventas()->vigentes()
            ->whereBetween('fecha', [$this->desde->toDateString(), $this->hasta->toDateString()])
            ->join('detalles_venta', 'detalles_venta.venta_id', '=', 'ventas.id')
            ->selectRaw('sum(detalles_venta.cantidad) as cantidad, sum(detalles_venta.subtotal) as total')
            ->toBase()
            ->first();

        return [
            'cemento' => round($cemento, 2),
            'arena' => round($arenaPorBloque, 2),
            'mano_de_obra' => round($manoDeObra, 2),
            'total' => round($cemento + $arenaPorBloque + $manoDeObra, 2),
            'precio_promedio_venta' => (int) $vendidos->cantidad > 0 ? round((float) $vendidos->total / (int) $vendidos->cantidad, 2) : 0.0,
        ];
    }
}
