<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Actions\Ventas\RegistrarVenta;
use App\Enums\FormaPago;
use App\Enums\ItemInventario;
use App\Exceptions\ReglaDeNegocio;
use App\Filament\Resources\Clientes\ClienteResource;
use App\Filament\Resources\Ventas\VentaResource;
use App\Filament\Support\Reglas;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Sucursal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    protected static ?string $title = 'Nueva venta';

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->placeholder('Consumidor final')
                            ->options(fn (): array => [])
                            ->getSearchResultsUsing(fn (string $search): array => Cliente::query()
                                ->where(fn ($query) => $query->whereLike('nombre', "%{$search}%")->orWhereLike('documento', "%{$search}%"))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (Cliente $cliente): array => [$cliente->id => $this->etiquetaCliente($cliente)])
                                ->all())
                            ->getOptionLabelUsing(fn (mixed $value): ?string => ($cliente = Cliente::find($value)) ? $this->etiquetaCliente($cliente) : null)
                            ->searchable()
                            ->createOptionForm(ClienteResource::campos())
                            ->createOptionUsing(fn (array $data): int => Cliente::create($data)->getKey())
                            ->required(fn (Get $get): bool => $this->esCredito($get('forma_pago')))
                            ->helperText('Busca por nombre o número de documento. Vacío = consumidor final.')
                            ->columnSpanFull(),
                        Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->options(fn (): array => Sucursal::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                            ->default(fn (): ?int => Sucursal::query()->count() === 1 ? Sucursal::query()->value('id') : null)
                            ->required()
                            ->live(),
                        DatePicker::make('fecha')
                            ->default(now())
                            ->maxDate(now())
                            ->required(),
                        ToggleButtons::make('forma_pago')
                            ->label('Forma de pago')
                            ->options(FormaPago::class)
                            ->default(FormaPago::Contado)
                            ->inline()
                            ->required()
                            ->live(),
                        DatePicker::make('fecha_vencimiento')
                            ->label('Fecha límite de pago')
                            ->helperText('Opcional. A fin de mes se avisa qué créditos están vencidos.')
                            ->afterOrEqual('fecha')
                            ->visible(fn (Get $get): bool => $this->esCredito($get('forma_pago'))),
                    ]),
                Section::make('Total')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('total_mostrado')
                            ->hiddenLabel()
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(fn (Get $get): string => Number::currency($this->total($get('lineas')), 'COP', 'es_CO', 0)),
                    ]),
                Repeater::make('lineas')
                    ->label('Productos')
                    ->columnSpanFull()
                    ->columns(3)
                    ->minItems(1)
                    ->addActionLabel('Agregar producto')
                    ->default(fn (): array => Producto::query()->count() === 1
                        ? [['producto_id' => Producto::query()->value('id'), 'cantidad' => null, 'precio' => (float) Producto::query()->value('precio_vigente')]]
                        : [[]])
                    ->live()
                    ->schema([
                        Select::make('producto_id')
                            ->label('Producto')
                            ->options(fn (): array => Producto::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('precio', $state ? (float) Producto::find($state)?->precio_vigente : null)),
                        TextInput::make('cantidad')
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->live(debounce: 400)
                            ->helperText(fn (Get $get): ?string => filled($get('../../sucursal_id'))
                                ? 'Hay '.Number::format(Sucursal::find($get('../../sucursal_id'))?->stock(ItemInventario::Bloque) ?? 0, locale: 'es_CO').' en la sucursal.'
                                : null),
                        TextInput::make('precio')
                            ->label('Precio por unidad')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->required()
                            ->live(debounce: 400)
                            ->helperText('Se puede cambiar solo para esta venta.'),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $credito = $this->esCredito($data['forma_pago']);

        try {
            return app(RegistrarVenta::class)(
                sucursal: Sucursal::findOrFail($data['sucursal_id']),
                lineas: collect($data['lineas'])->map(fn (array $linea): array => [
                    'producto' => Producto::findOrFail($linea['producto_id']),
                    'cantidad' => (int) $linea['cantidad'],
                    'precio' => (float) $linea['precio'],
                ])->values()->all(),
                formaPago: $credito ? FormaPago::Credito : FormaPago::Contado,
                fecha: Carbon::parse($data['fecha']),
                cliente: filled($data['cliente_id'] ?? null) ? Cliente::findOrFail($data['cliente_id']) : null,
                fechaVencimiento: $credito && filled($data['fecha_vencimiento'] ?? null) ? Carbon::parse($data['fecha_vencimiento']) : null,
                user: auth()->user(),
            );
        } catch (ReglaDeNegocio $e) {
            Reglas::notificar($e);

            $this->halt();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Venta registrada';
    }

    protected function getRedirectUrl(): string
    {
        return VentaResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    private function esCredito(mixed $formaPago): bool
    {
        return $formaPago === FormaPago::Credito || $formaPago === FormaPago::Credito->value;
    }

    /**
     * @param  array<array-key, array<string, mixed>>|null  $lineas
     */
    private function total(?array $lineas): float
    {
        return collect($lineas ?? [])->sum(fn (array $linea): float => (int) ($linea['cantidad'] ?? 0) * (float) ($linea['precio'] ?? 0));
    }

    private function etiquetaCliente(Cliente $cliente): string
    {
        return "{$cliente->nombre} · {$cliente->tipo_documento->value} {$cliente->documento}";
    }
}
