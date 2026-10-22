<?php

namespace App\Filament\Resources\Trabajadores;

use App\Filament\Resources\PagosTrabajador\PagoTrabajadorResource;
use App\Filament\Resources\Producciones\ProduccionResource;
use App\Filament\Resources\Trabajadores\Pages\ManageTrabajadores;
use App\Models\Sucursal;
use App\Models\Trabajador;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TrabajadorResource extends Resource
{
    protected static ?string $model = Trabajador::class;

    protected static ?string $slug = 'trabajadores';

    protected static ?string $modelLabel = 'trabajador';

    protected static ?string $pluralModelLabel = 'trabajadores';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sucursal_id')
                    ->label('Sucursal')
                    ->relationship('sucursal', 'nombre')
                    ->default(fn (): ?int => Sucursal::query()->count() === 1 ? Sucursal::query()->value('id') : null)
                    ->required(),
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('documento')
                    ->label('Cédula')
                    ->required()
                    ->maxLength(20)
                    ->scopedUnique(),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(20),
                Toggle::make('activo')
                    ->default(true)
                    ->helperText('Un trabajador inactivo no aparece al registrar producción, pero conserva su historial.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['sucursal', 'empresa'])
                ->withSum('producciones as acumulado', 'diferencia')
                ->withSum(['producciones as bultos_por_pagar' => fn (Builder $query) => $query->sinPagar()], 'bultos_usados'))
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('documento')
                    ->label('Cédula')
                    ->searchable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('acumulado')
                    ->label('Rendimiento')
                    ->tooltip('Bloques por encima (+) o por debajo (−) del estándar, sumando toda su producción.')
                    ->badge()
                    ->state(fn (Trabajador $record): int => (int) $record->acumulado)
                    ->color(fn (int $state): string => ProduccionResource::colorDiferencia($state))
                    ->formatStateUsing(fn (int $state): string => ProduccionResource::formatoDiferencia($state))
                    ->sortable(),
                TextColumn::make('bultos_por_pagar')
                    ->label('Bultos por pagar')
                    ->state(fn (Trabajador $record): int => (int) $record->bultos_por_pagar)
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->toggleable(),
                IconColumn::make('activo')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('sucursal')
                    ->relationship('sucursal', 'nombre'),
                TernaryFilter::make('activo')
                    ->default(true),
            ])
            ->recordActions([
                PagoTrabajadorResource::pagarAction(conTrabajador: true),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTrabajadores::route('/'),
        ];
    }
}
