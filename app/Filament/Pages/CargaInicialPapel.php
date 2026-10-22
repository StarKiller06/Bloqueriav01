<?php

namespace App\Filament\Pages;

use App\Actions\Arranque\CerrarCargaInicial;
use App\Actions\Arranque\GuardarCargaInicial;
use App\Enums\ItemInventario;
use App\Filament\Support\Reglas;
use App\Models\CargaInicial;
use App\Models\Empresa;
use App\Models\Sucursal;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use UnitEnum;

/**
 * Pasa al sistema lo que dice el papel. Se guarda las veces que haga falta hasta que coincida,
 * y luego se cierra. Solo el administrador.
 */
class CargaInicialPapel extends Page
{
    protected string $view = 'filament.pages.carga-inicial-papel';

    protected static ?string $title = 'Carga inicial';

    protected static ?string $slug = 'carga-inicial';

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->esAdministrador() ?? false;
    }

    public function mount(): void
    {
        $carga = $this->carga();
        $guardados = $carga?->detalles->keyBy('sucursal_id');

        $this->form->fill([
            'fecha_corte' => $carga?->fecha_corte ?? now()->startOfMonth(),
            'saldo_caja' => $carga?->saldo_caja,
            'sucursales' => $this->empresa()->sucursales()->orderBy('nombre')->get()
                ->map(fn (Sucursal $sucursal): array => [
                    'sucursal_id' => $sucursal->id,
                    'nombre' => $sucursal->nombre,
                    'bloques' => $guardados?->get($sucursal->id)?->bloques ?? 0,
                    'cemento' => $guardados?->get($sucursal->id)?->cemento ?? 0,
                    'arena_bloques' => $guardados?->get($sucursal->id)?->arena_bloques ?? 0,
                ])
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->disabled(fn (): bool => $this->carga()?->estaCerrada() ?? false)
            ->components([
                Section::make('Caja')
                    ->description('Del papel: el total de la caja el día de corte. Conviene arrancar el primer día de un mes, así el primer reporte mensual sale completo.')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('fecha_corte')
                            ->label('Fecha de corte')
                            ->maxDate(now())
                            ->required(),
                        TextInput::make('saldo_caja')
                            ->label('Total de caja según el papel')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->required(),
                    ]),
                Section::make('Sucursales')
                    ->description('Los bloques salen del papel. El cemento no está en el papel: cuéntalo ese día. La arena: para cuántos bloques alcanza la que queda.')
                    ->schema([
                        Repeater::make('sucursales')
                            ->hiddenLabel()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(4)
                            ->schema([
                                Hidden::make('sucursal_id'),
                                TextInput::make('nombre')
                                    ->label('Sucursal')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('bloques')
                                    ->integer()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('cemento')
                                    ->label('Cemento (bultos)')
                                    ->integer()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('arena_bloques')
                                    ->label('Arena alcanza para (bloques)')
                                    ->integer()
                                    ->minValue(0)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('guardar')
                ->label(fn (): string => $this->carga() ? 'Guardar cambios' : 'Guardar carga inicial')
                ->hidden(fn (): bool => $this->carga()?->estaCerrada() ?? false)
                ->action(function (Action $action, GuardarCargaInicial $guardar): void {
                    $datos = $this->form->getState();

                    Reglas::aplicar($action, fn () => $guardar(
                        empresa: $this->empresa(),
                        fechaCorte: Carbon::parse($datos['fecha_corte']),
                        saldoCaja: $datos['saldo_caja'],
                        sucursales: collect($datos['sucursales'])->mapWithKeys(fn (array $fila): array => [(int) $fila['sucursal_id'] => [
                            'bloques' => (int) $fila['bloques'],
                            'cemento' => (int) $fila['cemento'],
                            'arena_bloques' => (int) $fila['arena_bloques'],
                        ]])->all(),
                        user: auth()->user(),
                    ));

                    Notification::make()
                        ->success()
                        ->title('Carga guardada. Compárala con el papel.')
                        ->body($this->resumen())
                        ->persistent()
                        ->send();
                }),
            Action::make('cerrar')
                ->label('Cerrar carga inicial')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('success')
                ->visible(fn (): bool => $this->carga() !== null && ! $this->carga()->estaCerrada())
                ->requiresConfirmation()
                ->modalDescription(fn (): string => 'Confirma que esto coincide con el papel: '.$this->resumen().' Después de cerrar ya no se puede cambiar.')
                ->action(fn (Action $action, CerrarCargaInicial $cerrar) => Reglas::aplicar($action, fn () => $cerrar($this->carga())))
                ->successNotificationTitle('Carga inicial cerrada. Ya puedes dejar el papel.'),
        ];
    }

    public function getSubheading(): ?string
    {
        $carga = $this->carga();

        return match (true) {
            $carga === null => 'Aún no se ha hecho. Llena los datos del papel y guarda.',
            $carga->estaCerrada() => 'Cerrada el '.$carga->cerrada_at->format('d/m/Y').'. Ya no se puede cambiar.',
            default => 'Guardada, pendiente de cerrar. Puedes corregirla hasta que coincida con el papel.',
        };
    }

    /**
     * Cómo quedaron caja e inventario, para compararlo con el papel.
     */
    private function resumen(): string
    {
        $empresa = $this->empresa()->fresh();

        $sucursales = $empresa->sucursales()->orderBy('nombre')->get()
            ->map(fn (Sucursal $sucursal): string => sprintf(
                '%s: %s bloques y %s bultos',
                $sucursal->nombre,
                Number::format($sucursal->stock(ItemInventario::Bloque), locale: 'es_CO'),
                Number::format($sucursal->stock(ItemInventario::Cemento), locale: 'es_CO'),
            ))
            ->join('; ');

        return sprintf('Caja: %s. %s.', Number::currency($empresa->saldoCaja(), 'COP', 'es_CO', 0), $sucursales);
    }

    private function carga(): ?CargaInicial
    {
        return $this->empresa()->cargaInicial()->with('detalles')->first();
    }

    private function empresa(): Empresa
    {
        /** @var Empresa */
        return Filament::getTenant();
    }
}
