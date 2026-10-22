<?php

namespace App\Actions\Reportes;

use App\Models\Empresa;

/**
 * Si el mes está cerrado, devuelve el reporte guardado al cerrarlo; si no, lo calcula con los datos de hoy.
 */
class ObtenerReporteMensual
{
    public function __construct(private GenerarReporteMensual $generar) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Empresa $empresa, int $anio, int $mes): array
    {
        $cierre = $empresa->cierresMensuales()->where('anio', $anio)->where('mes', $mes)->first();

        return $cierre->reporte ?? ($this->generar)($empresa, $anio, $mes);
    }
}
