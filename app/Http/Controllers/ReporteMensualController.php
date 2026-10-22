<?php

namespace App\Http\Controllers;

use App\Actions\Reportes\ExportarReporteExcel;
use App\Actions\Reportes\ObtenerReporteMensual;
use App\Models\Empresa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReporteMensualController extends Controller
{
    /**
     * Descarga el reporte de un mes en PDF o en Excel.
     */
    public function __invoke(Request $request, Empresa $empresa, int $anio, int $mes, string $formato, ObtenerReporteMensual $obtener, ExportarReporteExcel $exportar): Response
    {
        abort_unless($request->user()->canAccessTenant($empresa), 403);
        abort_unless($mes >= 1 && $mes <= 12, 404);

        $reporte = $obtener($empresa, $anio, $mes);
        $archivo = sprintf('reporte-%04d-%02d', $anio, $mes);

        if ($formato === 'xlsx') {
            return response()->download($exportar($reporte), "{$archivo}.xlsx")->deleteFileAfterSend();
        }

        return Pdf::loadView('reportes.pdf', [
            'reporte' => $reporte,
            'cerrado' => $empresa->cierresMensuales()->where('anio', $anio)->where('mes', $mes)->exists(),
        ])->download("{$archivo}.pdf");
    }
}
