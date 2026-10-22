<?php

namespace App\Filament\Resources\Productos;

use App\Filament\Resources\Productos\Pages\ListProductos;
use App\Filament\Resources\Productos\Pages\ViewProducto;
use App\Filament\Resources\Productos\RelationManagers\HistorialPreciosRelationManager;
use App\Models\Producto;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;

    protected static ?string $modelLabel = 'producto';

    protected static ?string $pluralModelLabel = 'productos';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->scopedUnique(),
                TextInput::make('precio_vigente')
                    ->label('Precio por unidad')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->required()
                    ->visibleOn('create'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextEntry::make('nombre'),
                TextEntry::make('precio_vigente')
                    ->label('Precio vigente')
                    ->money(decimalPlaces: 0),
                TextEntry::make('updated_at')
                    ->label('Última actualización')
                    ->since(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('precio_vigente')
                    ->label('Precio vigente')
                    ->money(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Última actualización')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                static::ajustarPrecioAction(),
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    /**
     * Cambia el precio vigente. Cada cambio queda en el historial con quién lo hizo.
     */
    public static function ajustarPrecioAction(): Action
    {
        return Action::make('ajustarPrecio')
            ->label('Ajustar precio')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->authorize('update')
            ->fillForm(fn (Producto $record): array => ['precio_nuevo' => $record->precio_vigente])
            ->schema([
                TextInput::make('precio_nuevo')
                    ->label('Precio nuevo por unidad')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->required()
                    ->helperText('Las ventas ya hechas conservan su precio.'),
            ])
            ->action(fn (Producto $record, array $data) => $record->cambiarPrecio($data['precio_nuevo'], auth()->user()))
            ->successNotificationTitle('Precio actualizado');
    }

    public static function getRelations(): array
    {
        return [
            HistorialPreciosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductos::route('/'),
            'view' => ViewProducto::route('/{record}'),
        ];
    }
}
