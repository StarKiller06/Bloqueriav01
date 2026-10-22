<?php

namespace App\Filament\Resources\Clientes;

use App\Enums\TipoDocumento;
use App\Filament\Resources\Clientes\Pages\ManageClientes;
use App\Models\Cliente;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components(static::campos());
    }

    /**
     * También los usa la venta para registrar un cliente sin salir de ella.
     *
     * @return array<Field>
     */
    public static function campos(): array
    {
        return [
            Select::make('tipo_documento')
                ->label('Tipo de documento')
                ->options(TipoDocumento::class)
                ->default(TipoDocumento::Cedula)
                ->required()
                ->live(),
            TextInput::make('documento')
                ->label('Número de documento')
                ->required()
                ->maxLength(20)
                ->scopedUnique(Cliente::class, modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                    ->where('tipo_documento', $get('tipo_documento'))),
            TextInput::make('nombre')
                ->label('Nombre o razón social')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('email')
                ->label('Correo')
                ->email()
                ->maxLength(255)
                ->helperText('Aquí llegarán las facturas.'),
            TextInput::make('telefono')
                ->label('Teléfono')
                ->tel()
                ->maxLength(20),
            TextInput::make('direccion')
                ->label('Dirección')
                ->maxLength(255)
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withSum(['ventas as debe' => fn (Builder $query) => $query->porCobrar()], 'saldo_pendiente'))
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre o razón social')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo_documento')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (TipoDocumento $state): string => $state->value),
                TextColumn::make('documento')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label('Teléfono'),
                TextColumn::make('email')
                    ->label('Correo')
                    ->toggleable(),
                TextColumn::make('debe')
                    ->label('Debe')
                    ->money(decimalPlaces: 0)
                    ->placeholder('—')
                    ->color('danger')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo_documento')
                    ->label('Tipo de documento')
                    ->options(TipoDocumento::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Buscar un cliente por documento desde la barra de búsqueda del panel.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'documento'];
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Cliente $record */
        return ['Documento' => $record->tipo_documento->value.' '.$record->documento];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClientes::route('/'),
        ];
    }
}
