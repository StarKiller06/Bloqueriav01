<?php

namespace App\Actions\Reportes;

use App\Exceptions\ReglaDeNegocio;
use App\Models\CierreMensual;
use App\Models\Empresa;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Cierra un mes ya terminado: guarda su reporte y bloquea cualquier movimiento con fecha en ese mes.
 * Los meses se cierran en orden, y el primero solo después de cerrar la carga inicial.
 */
class CerrarMes
{
    public function __construct(private GenerarReporteMensual $generar) {}

    public function __invoke(Empresa $empresa, int $anio, int $mes, ?User $user = null): CierreMensual
    {
        $inicio = CarbonImmutable::create($anio, $mes, 1)->startOfMonth();
        $nombre = ucfirst($inicio->translatedFormat('F \d\e Y'));

        if (! $inicio->endOfMonth()->isPast()) {
            throw new ReglaDeNegocio("{$nombre} todavía no ha terminado.");
        }

        if ($empresa->mesCerrado($inicio)) {
            throw new ReglaDeNegocio("{$nombre} ya está cerrado.");
        }

        $carga = $empresa->cargaInicial()->first();

        if ($carga !== null && ! $carga->estaCerrada()) {
            throw new ReglaDeNegocio('Primero cierra la carga inicial, cuando coincida con el papel.');
        }

        $pendiente = $this->primerMesSinCerrarAntesDe($empresa, $inicio);

        if ($pendiente !== null) {
            throw new ReglaDeNegocio(sprintf('Primero hay que cerrar %s.', $pendiente->translatedFormat('F \d\e Y')));
        }

        return DB::transaction(function () use ($empresa, $anio, $mes, $user): CierreMensual {
            Empresa::query()->whereKey($empresa->getKey())->lockForUpdate()->first();

            return $empresa->cierresMensuales()->create([
                'anio' => $anio,
                'mes' => $mes,
                'reporte' => ($this->generar)($empresa, $anio, $mes),
                'user_id' => $user?->getKey(),
            ]);
        });
    }

    /**
     * El primer mes con movimientos, anterior a $inicio, que aún no se ha cerrado.
     */
    private function primerMesSinCerrarAntesDe(Empresa $empresa, CarbonImmutable $inicio): ?CarbonImmutable
    {
        $primeraFecha = collect([
            $empresa->movimientosCaja()->min('fecha'),
            $empresa->movimientosInventario()->min('fecha'),
        ])->filter()->min();

        if ($primeraFecha === null) {
            return null;
        }

        for ($mes = CarbonImmutable::parse($primeraFecha)->startOfMonth(); $mes->lt($inicio); $mes = $mes->addMonth()) {
            if (! $empresa->mesCerrado($mes)) {
                return $mes;
            }
        }

        return null;
    }
}
