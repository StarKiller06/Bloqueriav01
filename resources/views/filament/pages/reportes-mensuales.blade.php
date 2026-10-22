<x-filament-panels::page>
    <style>
        .reporte-pantalla .reporte { display: grid; gap: 1.5rem; }
        .reporte-pantalla section { border: 1px solid var(--gray-200); border-radius: 0.75rem; padding: 1rem 1.25rem; background: white; overflow-x: auto; }
        .dark .reporte-pantalla section { border-color: var(--gray-700); background: var(--gray-900); }
        .reporte-pantalla h2 { font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; }
        .reporte-pantalla table { width: 100%; border-collapse: collapse; font-size: 0.875rem; font-variant-numeric: tabular-nums; }
        .reporte-pantalla th { text-align: left; font-size: 0.75rem; font-weight: 500; color: var(--gray-500); padding: 0.4rem 0.5rem; border-bottom: 1px solid var(--gray-200); white-space: nowrap; }
        .reporte-pantalla td { padding: 0.4rem 0.5rem; border-bottom: 1px solid var(--gray-100); }
        .dark .reporte-pantalla th, .dark .reporte-pantalla td { border-color: var(--gray-800); }
        .reporte-pantalla .num { text-align: right; white-space: nowrap; }
        .reporte-pantalla .positivo { color: var(--success-600); }
        .reporte-pantalla .negativo { color: var(--danger-600); }
        .dark .reporte-pantalla .positivo { color: var(--success-400); }
        .dark .reporte-pantalla .negativo { color: var(--danger-400); }
        .reporte-pantalla .sangria { padding-left: 1.5rem; }
        .reporte-pantalla .subtitulo td { font-weight: 600; color: var(--gray-500); border-bottom: none; padding-top: 0.75rem; }
        .reporte-pantalla .resaltar td, .reporte-pantalla .total td { font-weight: 600; }
        .reporte-pantalla .total td { border-top: 2px solid var(--gray-300); border-bottom: none; }
        .reporte-pantalla .vacio, .reporte-pantalla .nota { color: var(--gray-500); font-size: 0.875rem; }
        .reporte-pantalla .nota { margin-top: 0.75rem; }
        @media (min-width: 1024px) { .reporte-pantalla .reporte { grid-template-columns: 1fr 1fr; } .reporte-pantalla section:nth-of-type(2), .reporte-pantalla section:nth-of-type(3) { grid-column: 1 / -1; } }
    </style>

    {{ $this->form }}

    @if ($this->cierre)
        <x-filament::callout icon="heroicon-o-lock-closed" color="success">
            <x-slot name="heading">Mes cerrado</x-slot>
            <x-slot name="description">
                Cerrado el {{ $this->cierre->created_at->format('d/m/Y') }}{{ $this->cierre->user ? ' por '.$this->cierre->user->name : '' }}. Las cifras son las que había al cerrar.
            </x-slot>
        </x-filament::callout>
    @else
        <x-filament::callout icon="heroicon-o-lock-open" color="warning">
            <x-slot name="heading">Mes abierto</x-slot>
            <x-slot name="description">Las cifras pueden cambiar mientras se registren movimientos con fecha de este mes.</x-slot>
        </x-filament::callout>
    @endif

    <div class="reporte-pantalla">
        @include('reportes.contenido', ['reporte' => $this->reporte])
    </div>
</x-filament-panels::page>
