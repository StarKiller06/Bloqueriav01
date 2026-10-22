<?php

namespace App\Filament\Resources\ConceptosCaja;

use App\Enums\TipoMovimiento;
use App\Filament\Resources\ConceptosCaja\Pages\ManageConceptosCaja;
use App\Models\ConceptoCaja;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ConceptoCajaResource extends Resource
{
    protected static ?string $model = ConceptoCaja::class;

    protected static ?string $slug = 'conceptos-caja';

    protected static ?string $modelLabel = 'concepto de caja';

    protected static ?string $pluralModelLabel = 'conceptos de caja';

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'nombre';

    /**
     * También lo usa el formulario de movimientos para crear un concepto sin salir de él.
     *
     * @return array<Component|Field>
     */
    public static function campos(): array
    {
        return [
            TextInput::make('nombre')
                ->required()
                ->maxLength(255)
                ->scopedUnique(ConceptoCaja::class),
            Select::make('tipo')
                ->options(TipoMovimiento::class)
                ->required(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::campos());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('movimientos'))
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->badge(),
                IconColumn::make('clave')
                    ->label('Del sistema')
                    ->state(fn (ConceptoCaja $record): bool => $record->esDelSistema())
                    ->boolean()
                    ->tooltip('Lo usan las ventas, compras y pagos. No se puede cambiar.'),
                TextColumn::make('movimientos_count')
                    ->label('Movimientos')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->options(TipoMovimiento::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageConceptosCaja::route('/'),
        ];
    }
}
