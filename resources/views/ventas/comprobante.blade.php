@php
    /** @var \App\Models\Venta $venta */
    $pesos = fn ($valor) => \Illuminate\Support\Number::currency((float) $valor, 'COP', 'es_CO', 0);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de venta {{ $venta->numeroFormateado() }}</title>
    <style>
        @page { margin: 32px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1d2225; }
        h1 { font-size: 18px; margin: 0; }
        .muted { color: #586266; }
        .encabezado { width: 100%; border-bottom: 2px solid #1d2225; padding-bottom: 10px; margin-bottom: 14px; }
        .encabezado td { vertical-align: top; }
        .numero { text-align: right; }
        .numero strong { font-size: 16px; }
        .datos { width: 100%; margin-bottom: 14px; }
        .datos td { padding: 2px 0; vertical-align: top; width: 50%; }
        .etiqueta { color: #586266; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.lineas { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.lineas th { text-align: left; border-bottom: 1px solid #1d2225; padding: 6px 4px; font-size: 9px; text-transform: uppercase; }
        table.lineas td { border-bottom: 1px solid #d3d8d6; padding: 6px 4px; }
        .num, table.lineas th.num { text-align: right; }
        table.totales { width: 45%; margin-left: 55%; border-collapse: collapse; }
        table.totales td { padding: 4px; }
        table.totales .total td { font-size: 14px; font-weight: bold; border-top: 2px solid #1d2225; }
        .saldo { color: #ae2a20; }
        .anulada { color: #ae2a20; font-size: 14px; font-weight: bold; border: 2px solid #ae2a20; padding: 6px; text-align: center; margin-bottom: 12px; }
        .pie { margin-top: 28px; font-size: 9px; color: #586266; border-top: 1px solid #d3d8d6; padding-top: 8px; }
    </style>
</head>
<body>
    <table class="encabezado">
        <tr>
            <td>
                <h1>{{ $venta->empresa->nombre }}</h1>
                <div class="muted">NIT {{ $venta->empresa->nit }}</div>
                <div class="muted">{{ $venta->sucursal->nombre }}@if ($venta->sucursal->direccion) · {{ $venta->sucursal->direccion }}@endif</div>
            </td>
            <td class="numero">
                <div class="etiqueta">Comprobante de venta</div>
                <strong>N.º {{ $venta->numeroFormateado() }}</strong>
                <div>{{ $venta->fecha->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    @if ($venta->estaAnulada())
        <div class="anulada">ANULADA · {{ $venta->motivo_anulacion }}</div>
    @endif

    <table class="datos">
        <tr>
            <td>
                <div class="etiqueta">Cliente</div>
                <div>{{ $venta->nombreCliente() }}</div>
                @if ($venta->cliente)
                    <div class="muted">{{ $venta->cliente->tipo_documento->value }} {{ $venta->cliente->documento }}</div>
                    @if ($venta->cliente->direccion)<div class="muted">{{ $venta->cliente->direccion }}</div>@endif
                    @if ($venta->cliente->telefono)<div class="muted">Tel. {{ $venta->cliente->telefono }}</div>@endif
                @endif
            </td>
            <td>
                <div class="etiqueta">Forma de pago</div>
                <div>{{ $venta->forma_pago->getLabel() }}</div>
                @if ($venta->fecha_vencimiento)
                    <div class="muted">Pagar a más tardar el {{ $venta->fecha_vencimiento->format('d/m/Y') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="lineas">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="num">Cantidad</th>
                <th class="num">Precio unitario</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($venta->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto->nombre }}</td>
                    <td class="num">{{ \Illuminate\Support\Number::format($detalle->cantidad, locale: 'es_CO') }}</td>
                    <td class="num">{{ $pesos($detalle->precio_unitario) }}</td>
                    <td class="num">{{ $pesos($detalle->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr class="total">
            <td>Total</td>
            <td class="num">{{ $pesos($venta->total) }}</td>
        </tr>
        @if ($venta->forma_pago === \App\Enums\FormaPago::Credito && ! $venta->estaAnulada())
            @foreach ($venta->abonos as $abono)
                <tr>
                    <td class="muted">Abono {{ $abono->fecha->format('d/m/Y') }}</td>
                    <td class="num muted">− {{ $pesos($abono->monto) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Saldo pendiente</strong></td>
                <td class="num saldo"><strong>{{ $pesos($venta->saldo_pendiente) }}</strong></td>
            </tr>
        @endif
    </table>

    <div class="pie">
        Este comprobante no reemplaza la factura electrónica de la DIAN.
    </div>
</body>
</html>
