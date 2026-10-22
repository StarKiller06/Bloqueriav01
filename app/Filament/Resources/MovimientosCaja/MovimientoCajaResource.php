<?php

namespace App\Filament\Resources\MovimientosCaja;

use App\Enums\TipoMovimiento;
use App\Exceptions\ReglaDeNegocio;
use App\Filament\Resources\ConceptosCaja\ConceptoCajaResource;
use App\Filament\Resources\MovimientosCaja\Pages\ManageMovimientosCaja;
use App\Filament\Support\Reglas;
use App\Models\MovimientoCaja;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use UnitEnum;

class MovimientoCajaResource extends Resource
{
    protected static ?string $model = MovimientoCaja::class;

    protected static ?string $slug = 'caja';

    protected static ?string $modelLabel = 'movimiento';

    protected static ?string $pluralModelLabel = 'movimientos de caja';

    protected static ?string $navigationLabel = 'Caja';

    protected static string|UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    /**
     * Campos de una entrada o un gasto manual. El concepto solo ofrece los del tipo elegido
     * y se puede crear uno nuevo desde el mismo campo.
     *
     * @return array<Component|Field>
     */
    public static function campos(TipoMovimiento $tipo): array
    {
        return [
            Select::make('concepto_caja_id')
                ->label('Concepto')
                ->relationship(
                    'concepto',
                    'nombre',
                    fn (Builder $query): Builder => $query->manuales()->where('tipo', $tipo),
                )
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    ConceptoCajaResource::campos()[0],
                    Hidden::make('tipo')->default($tipo->value),
                ]),
            TextInput::make('monto')
                ->numeric()
                ->minValue(1)
                ->prefix('$')
                ->required(),
            DatePicker::make('fecha')
                ->default(now())
                ->maxDate(now())
                ->required(),
            Select::make('sucursal_id')
                ->label('Sucursal')
                ->relationship('sucursal', 'nombre')
                ->placeholder('General de la empresa'),
            TextInput::make('nota')
                ->maxLength(255)
                ->columnSpanFull(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        /** @var MovimientoCaja|null $record */
        $record = $schema->getRecord();

        return $schema
            ->columns(2)
            ->components(static::campos($record->tipo ?? TipoMovimiento::Entrada));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['concepto', 'sucursal', 'user']))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('fecha')->orderByDesc('id'))
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('concepto.nombre')
                    ->label('Concepto')
                    ->searchable(),
                TextColumn::make('monto')
                    ->money(decimalPlaces: 0)
                    ->prefix(fn (MovimientoCaja $record): string => $record->tipo === TipoMovimiento::Entrada ? '+ ' : '− ')
                    ->color(fn (MovimientoCaja $record): string => $record->tipo->getColor())
                    ->alignEnd()
                    ->summarize(
                        Summarizer::make()
                            ->label('Neto')
                            ->using(fn (QueryBuilder $query): float => (float) $query->sum(DB::raw(
                                "case when tipo = '".TipoMovimiento::Entrada->value."' then monto else -monto end"
                            )))
                            ->formatStateUsing(fn (float $state): string => Number::currency($state, 'COP', 'es_CO', 0)),
                    ),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->placeholder('General'),
                TextColumn::make('nota')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Registró')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->options(TipoMovimiento::class),
                SelectFilter::make('concepto')
                    ->relationship('concepto', 'nombre'),
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
                EditAction::make()
                    ->using(fn (MovimientoCaja $record, array $data, EditAction $action): MovimientoCaja => Reglas::aplicar($action, function () use ($record, $data): MovimientoCaja {
                        $fecha = Carbon::parse($data['fecha']);

                        if ($record->empresa->mesCerrado($fecha)) {
                            throw ReglaDeNegocio::mesCerrado($fecha);
                        }

                        $record->update([...$data, 'tipo' => $record->tipo]);

                        return $record;
                    })),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMovimientosCaja::route('/'),
        ];
    }
}
