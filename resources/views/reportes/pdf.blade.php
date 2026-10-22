<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte {{ $reporte['periodo']['nombre'] }}</title>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1d2225; }
        .cabecera { border-bottom: 2px solid #1d2225; padding-bottom: 8px; margin-bottom: 6px; }
        .cabecera h1 { font-size: 17px; margin: 0; }
        .muted { color: #586266; }
        .estado { float: right; text-align: right; }
        h2 { font-size: 12px; margin: 16px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #d3d8d6; }
        section { page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 8px; text-transform: uppercase; color: #586266; border-bottom: 1px solid #1d2225; padding: 4px 3px; }
        td { padding: 4px 3px; border-bottom: 1px solid #eceeed; }
        .num, th.num { text-align: right; }
        .positivo { color: #1d7442; }
        .negativo { color: #ae2a20; }
        .sangria { padding-left: 14px; }
        .subtitulo td { font-weight: bold; color: #586266; border-bottom: none; padding-top: 8px; }
        .resaltar td { font-weight: bold; }
        .total td { font-weight: bold; border-top: 1.5px solid #1d2225; border-bottom: none; }
        .vacio { color: #586266; }
        .nota { color: #586266; font-size: 9px; margin: 6px 0 0; }
    </style>
</head>
<body>
    <div class="cabecera">
        <div class="estado">
            <strong>{{ $reporte['periodo']['nombre'] }}</strong><br>
            <span class="muted">{{ $cerrado ? 'Mes cerrado' : 'Mes abierto: las cifras aún pueden cambiar' }}</span>
        </div>
        <h1>{{ $reporte['empresa']['nombre'] }}</h1>
        <div class="muted">NIT {{ $reporte['empresa']['nit'] }} · Reporte mensual</div>
    </div>

    @include('reportes.contenido', ['reporte' => $reporte])

    <p class="nota">Generado el {{ \Illuminate\Support\Carbon::parse($reporte['generado_en'])->format('d/m/Y h:i a') }}.</p>
</body>
</html>
