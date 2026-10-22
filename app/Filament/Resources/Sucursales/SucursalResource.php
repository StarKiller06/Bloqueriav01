<?php

namespace App\Filament\Resources\Sucursales;

use App\Filament\Resources\Sucursales\Pages\ManageSucursales;
use App\Models\Sucursal;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SucursalResource extends Resource
{
    protected static ?string $model = Sucursal::class;

    protected static ?string $slug = 'sucursales';

    protected static ?string $modelLabel = 'sucursal';

    protected static ?string $pluralModelLabel = 'sucursales';

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->scopedUnique(),
                TextInput::make('direccion')
                    ->label('Dirección')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('direccion')
                    ->label('Dirección')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSucursales::route('/'),
        ];
    }
}
