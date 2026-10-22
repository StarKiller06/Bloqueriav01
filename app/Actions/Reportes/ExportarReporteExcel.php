<?php

namespace App\Actions\Reportes;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Escribe el reporte mensual en un archivo de Excel, una hoja por sección.
 * Los números van como números (no como texto) para que se puedan sumar en Excel.
 */
class ExportarReporteExcel
{
    private Writer $writer;

    private Style $negrita;

    /**
     * @param  array<string, mixed>  $reporte
     * @return string ruta del archivo temporal
     */
    public function __invoke(array $reporte): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'reporte').'.xlsx';

        $this->writer = new Writer;
        $this->negrita = (new Style)->setFontBold();
        $this->writer->openToFile($ruta);

        $this->hoja('Caja', function () use ($reporte): void {
            $caja = $reporte['caja'];
            $this->titulo("{$reporte['empresa']['nombre']} · {$reporte['periodo']['nombre']}");
            $this->fila(['Saldo al iniciar el mes', $caja['saldo_inicial']], true);
            $this->fila(['Entradas'], true);
            foreach ($caja['entradas'] as $fila) {
                $this->fila([$fila['concepto'], $fila['total']]);
            }
            $this->fila(['Gastos'], true);
            foreach ($caja['gastos'] as $fila) {
                $this->fila([$fila['concepto'], -$fila['total']]);
            }
            $this->fila(['Saldo al terminar el mes', $caja['saldo_final']], true);
        }, primera: true);

        foreach (['bloques' => 'Bloques', 'cemento' => 'Cemento'] as $item => $nombre) {
            $this->hoja($nombre, function () use ($reporte, $item): void {
                $columnas = array_keys($reporte['inventario']['general'][$item]);
                $this->fila(['Sucursal', ...$columnas], true);
                foreach ($reporte['inventario']['sucursales'] as $fila) {
                    $this->fila([$fila['sucursal'], ...array_values($fila[$item])]);
                }
                $this->fila(['General', ...array_values($reporte['inventario']['general'][$item])], true);
            });
        }

        $this->tabla('Arena', ['Sucursal', 'Viajes', 'Costo', 'Bloques estimados', 'Bloques producidos'], $reporte['arena']);
        $this->tabla('Trabajadores', ['Trabajador', 'Sucursal', 'Bultos', 'Bloques', 'Diferencia', 'Pagado'], $reporte['trabajadores']);
        $this->tabla('Ventas', ['Sucursal', 'Ventas', 'Bloques', 'Contado', 'Crédito'], $reporte['ventas']['por_sucursal']);
        $this->tabla('Cartera', ['Cliente', 'Debía al terminar el mes'], $reporte['ventas']['cartera']);

        $this->writer->close();

        return $ruta;
    }

    /**
     * @param  list<string>  $encabezados
     * @param  list<array<string, mixed>>  $filas
     */
    private function tabla(string $nombre, array $encabezados, array $filas): void
    {
        $this->hoja($nombre, function () use ($encabezados, $filas): void {
            $this->fila($encabezados, true);
            foreach ($filas as $fila) {
                $this->fila(array_values($fila));
            }
        });
    }

    private function hoja(string $nombre, callable $contenido, bool $primera = false): void
    {
        $hoja = $primera ? $this->writer->getCurrentSheet() : $this->writer->addNewSheetAndMakeItCurrent();
        $hoja->setName($nombre);
        $contenido();
    }

    private function titulo(string $texto): void
    {
        $this->fila([$texto], true);
        $this->fila([]);
    }

    /**
     * @param  list<mixed>  $valores
     */
    private function fila(array $valores, bool $negrita = false): void
    {
        $this->writer->addRow(Row::fromValues($valores, $negrita ? $this->negrita : null));
    }
}
