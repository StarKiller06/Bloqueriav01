<?php

namespace App\Filament\Resources\Ventas;

use App\Actions\Ventas\AnularVenta;
use App\Actions\Ventas\RegistrarAbono;
use App\Enums\EstadoVenta;
use App\Enums\FormaPago;
use App\Filament\Resources\Ventas\Pages\CreateVenta;
use App\Filament\Resources\Ventas\Pages\ListVentas;
use App\Filament\Resources\Ventas\Pages\ViewVenta;
use App\Filament\Resources\Ventas\RelationManagers\AbonosRelationManager;
use App\Filament\Support\Reglas;
use App\Models\Venta;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use UnitEnum;

class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $modelLabel = 'venta';

    protected static ?string $pluralModelLabel = 'ventas';

    protected static string|UnitEnum|null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $recordTitleAttribute = 'numero';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('numero')
                            ->label('Venta')
                            ->formatStateUsing(fn (Venta $record): string => $record->numeroFormateado()),
                        TextEntry::make('fecha')
                            ->date('d/m/Y'),
                        TextEntry::make('cliente')
                            ->state(fn (Venta $record): string => $record->nombreCliente()),
                        TextEntry::make('sucursal.nombre')
                            ->label('Sucursal'),
                        TextEntry::make('forma_pago')
                            ->label('Forma de pago')
                            ->badge(),
                        TextEntry::make('estado')
                            ->state(fn (Venta $record): EstadoVenta => $record->estado())
                            ->badge(),
                        TextEntry::make('fecha_vencimiento')
                            ->label('Fecha límite de pago')
                            ->date('d/m/Y')
                            ->placeholder('Sin fecha límite')
                            ->visible(fn (Venta $record): bool => $record->forma_pago === FormaPago::Credito),
                        TextEntry::make('motivo_anulacion')
                            ->label('Motivo de anulación')
                            ->visible(fn (Venta $record): bool => $record->estaAnulada()),
                    ]),
                RepeatableEntry::make('detalles')
                    ->label('Productos')
                    ->columnSpan(2)
                    ->table([
                        TableColumn::make('Producto'),
                        TableColumn::make('Cantidad')->alignEnd(),
                        TableColumn::make('Precio')->alignEnd(),
                        TableColumn::make('Subtotal')->alignEnd(),
                    ])
                    ->schema([
                        TextEntry::make('producto.nombre'),
                        TextEntry::make('cantidad')->numeric(),
                        TextEntry::make('precio_unitario')->money(decimalPlaces: 0),
                        TextEntry::make('subtotal')->money(decimalPlaces: 0),
                    ]),
                Section::make('Totales')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('total')
                            ->money(decimalPlaces: 0)
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('saldo_pendiente')
                            ->label('Saldo pendiente')
                            ->money(decimalPlaces: 0)
                            ->color(fn (Venta $record): string => (float) $record->saldo_pendiente > 0 ? 'danger' : 'success')
                            ->visible(fn (Venta $record): bool => $record->forma_pago === FormaPago::Credito),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['cliente', 'sucursal']))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('fecha')->orderByDesc('numero'))
            ->columns([
                TextColumn::make('numero')
                    ->label('N.º')
                    ->formatStateUsing(fn (Venta $record): string => $record->numeroFormateado())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->placeholder('Consumidor final')
                    ->searchable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->toggleable(),
                TextColumn::make('forma_pago')
                    ->label('Pago')
                    ->badge(),
                TextColumn::make('total')
                    ->money(decimalPlaces: 0)
                    ->alignEnd(),
                TextColumn::make('saldo_pendiente')
                    ->label('Saldo')
                    ->money(decimalPlaces: 0)
                    ->alignEnd()
                    ->color(fn (string $state): string => (float) $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('estado')
                    ->state(fn (Venta $record): EstadoVenta => $record->estado())
                    ->badge(),
                TextColumn::make('fecha_vencimiento')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->options(EstadoVenta::class)
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->enEstado(EstadoVenta::from($data['value']))
                        : $query),
                SelectFilter::make('forma_pago')
                    ->label('Forma de pago')
                    ->options(FormaPago::class),
                SelectFilter::make('cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable(),
                SelectFilter::make('sucursal')
                    ->relationship('sucursal', 'nombre'),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $query, string $desde) => $query->whereDate('fecha', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $query, string $hasta) => $query->whereDate('fecha', '<=', $hasta))),
            ])
            ->recordActions([
                static::abonarAction(),
                ViewAction::make(),
            ]);
    }

    public static function abonarAction(): Action
    {
        return Action::make('abonar')
            ->label('Abonar')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->authorize('abonar')
            ->modalHeading(fn (Venta $record): string => "Abono a la venta {$record->numeroFormateado()}")
            ->modalDescription(fn (Venta $record): string => sprintf(
                '%s debe %s.',
                $record->nombreCliente(),
                Number::currency((float) $record->saldo_pendiente, 'COP', 'es_CO', 0),
            ))
            ->fillForm(fn (Venta $record): array => ['monto' => (float) $record->saldo_pendiente, 'fecha' => now()->toDateString()])
            ->schema([
                TextInput::make('monto')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn (Venta $record): float => (float) $record->saldo_pendiente)
                    ->prefix('$')
                    ->required(),
                DatePicker::make('fecha')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                TextInput::make('nota')
                    ->maxLength(255),
            ])
            ->action(fn (Venta $record, array $data, Action $action, RegistrarAbono $abonar) => Reglas::aplicar($action, fn () => $abonar(
                $record, $data['monto'], Carbon::parse($data['fecha']), auth()->user(), $data['nota'] ?? null,
            )))
            ->successNotificationTitle('Abono registrado');
    }

    public static function anularAction(): Action
    {
        return Action::make('anular')
            ->label('Anular')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('anular')
            ->requiresConfirmation()
            ->modalDescription('Los bloques vuelven a la sucursal y lo que el cliente pagó sale de la caja para devolvérselo.')
            ->schema([
                TextInput::make('motivo')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(fn (Venta $record, array $data, Action $action, AnularVenta $anular) => Reglas::aplicar($action, fn () => $anular(
                $record, $data['motivo'], now(), auth()->user(),
            )))
            ->successNotificationTitle('Venta anulada');
    }

    public static function getRelations(): array
    {
        return [
            AbonosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVentas::route('/'),
            'create' => CreateVenta::route('/nueva'),
            'view' => ViewVenta::route('/{record}'),
        ];
    }
}
