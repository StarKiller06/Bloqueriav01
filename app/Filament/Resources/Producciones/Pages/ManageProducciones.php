<?php

namespace App\Filament\Resources\Producciones\Pages;

use App\Actions\Produccion\RegistrarProduccion;
use App\Filament\Resources\Producciones\ProduccionResource;
use App\Filament\Support\Reglas;
use App\Filament\Widgets\ResumenInventario;
use App\Models\Empresa;
use App\Models\Produccion;
use App\Models\Producto;
use App\Models\Trabajador;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;

class ManageProducciones extends ManageRecords
{
    protected static string $resource = ProduccionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ResumenInventario::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        /** @var Empresa $empresa */
        $empresa = Filament::getTenant();

        return [
            CreateAction::make('registrar')
                ->label('Registrar producción')
                ->modalHeading('Registrar producción')
                ->schema([
                    Select::make('trabajador_id')
                        ->label('Trabajador')
                        ->options(fn (): array => Trabajador::query()->activos()->with('sucursal')->orderBy('nombre')->get()
                            ->mapWithKeys(fn (Trabajador $trabajador): array => [$trabajador->id => "{$trabajador->nombre} · {$trabajador->sucursal->nombre}"])
                            ->all())
                        ->searchable()
                        ->required()
                        ->helperText('Los bloques entran a la sucursal del trabajador y de ahí sale el cemento.'),
                    Select::make('producto_id')
                        ->label('Producto')
                        ->relationship('producto', 'nombre')
                        ->default(fn (): ?int => Producto::query()->count() === 1 ? Producto::query()->value('id') : null)
                        ->required(),
                    DatePicker::make('fecha')
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    TextInput::make('bultos_usados')
                        ->label('Bultos de cemento usados')
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->live(debounce: 400),
                    TextInput::make('bloques_sacados')
                        ->label('Bloques sacados')
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->live(debounce: 400)
                        ->helperText(fn (Get $get): ?string => $this->resumen($empresa, $get('bultos_usados'), $get('bloques_sacados'))),
                ])
                ->using(fn (array $data, CreateAction $action, RegistrarProduccion $registrar): Produccion => Reglas::aplicar($action, fn (): Produccion => $registrar(
                    trabajador: Trabajador::query()->whereKey($data['trabajador_id'])->activos()->firstOrFail(),
                    producto: Producto::findOrFail($data['producto_id']),
                    bultosUsados: (int) $data['bultos_usados'],
                    bloquesSacados: (int) $data['bloques_sacados'],
                    fecha: Carbon::parse($data['fecha']),
                    user: auth()->user(),
                )))
                ->successNotificationTitle(fn (Produccion $record): string => match (true) {
                    $record->diferencia > 0 => "Registrado: {$record->diferencia} bloques por encima del estándar",
                    $record->diferencia < 0 => 'Registrado: '.abs($record->diferencia).' bloques por debajo del estándar',
                    default => 'Registrado: justo en el estándar',
                }),
        ];
    }

    /**
     * Muestra en vivo el esperado y la diferencia mientras se llena el formulario.
     */
    private function resumen(Empresa $empresa, mixed $bultos, mixed $sacados): ?string
    {
        if (! is_numeric($bultos) || (int) $bultos <= 0) {
            return null;
        }

        $esperado = (int) $bultos * $empresa->bloques_por_bulto;

        if (! is_numeric($sacados)) {
            return "Esperado: {$esperado} bloques";
        }

        return sprintf('Esperado: %d · Diferencia: %s', $esperado, ProduccionResource::formatoDiferencia((int) $sacados - $esperado));
    }
}
