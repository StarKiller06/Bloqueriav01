<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ComprobanteVentaController extends Controller
{
    /**
     * Muestra el comprobante de una venta en PDF.
     */
    public function __invoke(Venta $venta): Response
    {
        Gate::authorize('view', $venta);

        return static::pdf($venta)->stream("comprobante-{$venta->numeroFormateado()}.pdf");
    }

    public static function pdf(Venta $venta): DomPdf
    {
        $venta->loadMissing(['empresa', 'sucursal', 'cliente', 'detalles.producto', 'abonos']);

        return Pdf::loadView('ventas.comprobante', ['venta' => $venta])->setPaper('letter');
    }
}
