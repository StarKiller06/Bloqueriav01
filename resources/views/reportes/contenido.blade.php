{{-- Contenido del reporte mensual. Lo usan la pantalla y el PDF; cada uno pone sus estilos. --}}
@php
    $pesos = fn ($valor) => \Illuminate\Support\Number::currency((float) $valor, 'COP', 'es_CO', 0);
    $num = fn ($valor) => \Illuminate\Support\Number::format((int) $valor, locale: 'es_CO');
    $signo = fn ($valor) => ((int) $valor > 0 ? '+' : '') . $num($valor);
    $claseSigno = fn ($valor) => (int) $valor > 0 ? 'positivo' : ((int) $valor < 0 ? 'negativo' : '');
@endphp

<div class="reporte">
    <section>
        <h2>Caja</h2>
        <table>
            <tbody>
                <tr class="resaltar"><td>Saldo al iniciar el mes</td><td class="num">{{ $pesos($reporte['caja']['saldo_inicial']) }}</td></tr>
                <tr class="subtitulo"><td colspan="2">Entradas</td></tr>
                @forelse ($reporte['caja']['entradas'] as $fila)
                    <tr><td class="sangria">{{ $fila['concepto'] }}</td><td class="num positivo">+ {{ $pesos($fila['total']) }}</td></tr>
                @empty
                    <tr><td class="sangria vacio" colspan="2">Sin entradas</td></tr>
                @endforelse
                <tr class="subtitulo"><td colspan="2">Gastos</td></tr>
                @forelse ($reporte['caja']['gastos'] as $fila)
                    <tr><td class="sangria">{{ $fila['concepto'] }}</td><td class="num negativo">− {{ $pesos($fila['total']) }}</td></tr>
                @empty
                    <tr><td class="sangria vacio" colspan="2">Sin gastos</td></tr>
                @endforelse
                <tr class="total"><td>Saldo al terminar el mes</td><td class="num">{{ $pesos($reporte['caja']['saldo_final']) }}</td></tr>
            </tbody>
        </table>
    </section>

    @foreach (['bloques' => 'Bloques (unidades)', 'cemento' => 'Cemento (bultos)'] as $item => $titulo)
        <section>
            <h2>{{ $titulo }}</h2>
            @php $columnas = array_keys($reporte['inventario']['general'][$item]); @endphp
            <table>
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        @foreach ($columnas as $columna)
                            <th class="num">{{ ['inicial' => 'Inicial', 'apertura' => 'Carga inicial', 'produccion' => 'Producción', 'ventas' => 'Ventas', 'traslados' => 'Traslados', 'ajustes' => 'Ajustes', 'compras' => 'Compras', 'consumo' => 'Consumo', 'final' => 'Final'][$columna] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reporte['inventario']['sucursales'] as $fila)
                        <tr>
                            <td>{{ $fila['sucursal'] }}</td>
                            @foreach ($columnas as $columna)
                                <td class="num">{{ in_array($columna, ['inicial', 'final']) ? $num($fila[$item][$columna]) : $signo($fila[$item][$columna]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td>General</td>
                        @foreach ($columnas as $columna)
                            <td class="num">{{ in_array($columna, ['inicial', 'final']) ? $num($reporte['inventario']['general'][$item][$columna]) : $signo($reporte['inventario']['general'][$item][$columna]) }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </section>
    @endforeach

    <section>
        <h2>Arena</h2>
        <table>
            <thead><tr><th>Sucursal</th><th class="num">Viajes</th><th class="num">Costo</th><th class="num">Alcanza para</th><th class="num">Bloques producidos</th></tr></thead>
            <tbody>
                @foreach ($reporte['arena'] as $fila)
                    <tr>
                        <td>{{ $fila['sucursal'] }}</td>
                        <td class="num">{{ $num($fila['viajes']) }}</td>
                        <td class="num">{{ $pesos($fila['costo']) }}</td>
                        <td class="num">{{ $num($fila['bloques_estimados']) }}</td>
                        <td class="num">{{ $num($fila['bloques_producidos']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section>
        <h2>Trabajadores</h2>
        @if ($reporte['trabajadores'] === [])
            <p class="vacio">Nadie produjo ni recibió pagos este mes.</p>
        @else
            <table>
                <thead><tr><th>Trabajador</th><th>Sucursal</th><th class="num">Bultos</th><th class="num">Bloques</th><th class="num">Diferencia</th><th class="num">Pagado</th></tr></thead>
                <tbody>
                    @foreach ($reporte['trabajadores'] as $fila)
                        <tr>
                            <td>{{ $fila['nombre'] }}</td>
                            <td>{{ $fila['sucursal'] }}</td>
                            <td class="num">{{ $num($fila['bultos']) }}</td>
                            <td class="num">{{ $num($fila['sacados']) }}</td>
                            <td class="num {{ $claseSigno($fila['diferencia']) }}">{{ $signo($fila['diferencia']) }}</td>
                            <td class="num">{{ $pesos($fila['pagado']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section>
        <h2>Ventas</h2>
        <table>
            <thead><tr><th>Sucursal</th><th class="num">Ventas</th><th class="num">Bloques</th><th class="num">Contado</th><th class="num">Crédito</th></tr></thead>
            <tbody>
                @foreach ($reporte['ventas']['por_sucursal'] as $fila)
                    <tr>
                        <td>{{ $fila['sucursal'] }}</td>
                        <td class="num">{{ $num($fila['cantidad']) }}</td>
                        <td class="num">{{ $num($fila['bloques']) }}</td>
                        <td class="num">{{ $pesos($fila['contado']) }}</td>
                        <td class="num">{{ $pesos($fila['credito']) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>General</td>
                    <td class="num">{{ $num(array_sum(array_column($reporte['ventas']['por_sucursal'], 'cantidad'))) }}</td>
                    <td class="num">{{ $num(array_sum(array_column($reporte['ventas']['por_sucursal'], 'bloques'))) }}</td>
                    <td class="num">{{ $pesos($reporte['ventas']['total_contado']) }}</td>
                    <td class="num">{{ $pesos($reporte['ventas']['total_credito']) }}</td>
                </tr>
            </tbody>
        </table>
        <p class="nota">Abonos recibidos en el mes: <strong>{{ $pesos($reporte['ventas']['abonos']) }}</strong> · Ventas anuladas: <strong>{{ $num($reporte['ventas']['anuladas']) }}</strong></p>
    </section>

    <section>
        <h2>Cartera al terminar el mes</h2>
        @if ($reporte['ventas']['cartera'] === [])
            <p class="vacio">Ningún cliente debía al terminar el mes.</p>
        @else
            <table>
                <thead><tr><th>Cliente</th><th class="num">Debía</th></tr></thead>
                <tbody>
                    @foreach ($reporte['ventas']['cartera'] as $fila)
                        <tr><td>{{ $fila['cliente'] }}</td><td class="num negativo">{{ $pesos($fila['saldo']) }}</td></tr>
                    @endforeach
                    <tr class="total"><td>Total por cobrar</td><td class="num">{{ $pesos(array_sum(array_column($reporte['ventas']['cartera'], 'saldo'))) }}</td></tr>
                </tbody>
            </table>
        @endif
    </section>

    <section>
        <h2>Costo por bloque (estimado)</h2>
        @if ($reporte['costo_bloque'] === null)
            <p class="vacio">No hubo producción este mes.</p>
        @else
            <table>
                <tbody>
                    <tr><td>Cemento</td><td class="num">{{ $pesos($reporte['costo_bloque']['cemento']) }}</td></tr>
                    <tr><td>Arena</td><td class="num">{{ $pesos($reporte['costo_bloque']['arena']) }}</td></tr>
                    <tr><td>Mano de obra</td><td class="num">{{ $pesos($reporte['costo_bloque']['mano_de_obra']) }}</td></tr>
                    <tr class="total"><td>Costo por bloque</td><td class="num">{{ $pesos($reporte['costo_bloque']['total']) }}</td></tr>
                    <tr><td>Precio promedio de venta</td><td class="num">{{ $pesos($reporte['costo_bloque']['precio_promedio_venta']) }}</td></tr>
                </tbody>
            </table>
            <p class="nota">Usa el precio promedio del cemento y la arena comprados hasta este mes y la tarifa por bulto actual.</p>
        @endif
    </section>
</div>
