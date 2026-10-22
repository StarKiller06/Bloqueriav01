<x-mail::message>
# Gracias por su compra

Adjuntamos el comprobante de la venta **N.º {{ $venta->numeroFormateado() }}** del {{ $venta->fecha->format('d/m/Y') }}.

**Total:** {{ \Illuminate\Support\Number::currency((float) $venta->total, 'COP', 'es_CO', 0) }}

@if ((float) $venta->saldo_pendiente > 0)
**Saldo pendiente:** {{ \Illuminate\Support\Number::currency((float) $venta->saldo_pendiente, 'COP', 'es_CO', 0) }}@if ($venta->fecha_vencimiento), a pagar a más tardar el {{ $venta->fecha_vencimiento->format('d/m/Y') }}@endif.
@endif

{{ $venta->empresa->nombre }}
</x-mail::message>
