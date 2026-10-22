<?php

namespace App\Filament\Resources\ViajesArena;

use App\Filament\Resources\ViajesArena\Pages\ManageViajesArena;
use App\Models\ViajeArena;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ViajeArenaResource extends Resource
{
    protected static ?string $model = ViajeArena::class;

    protected static ?string $slug = 'viajes-arena';

    protected static ?string $modelLabel = 'viaje de arena';

    protected static ?string $pluralModelLabel = 'viajes de arena';

    protected static string|UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('sucursal'))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('fecha')->orderByDesc('id'))
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal'),
                TextColumn::make('metros_cubicos')
                    ->label('m³')
                    ->numeric(decimalPlaces: 1)
                    ->alignEnd(),
                TextColumn::make('costo')
                    ->money(decimalPlaces: 0)
                    ->alignEnd()
                    ->summarize(Sum::make()->label('Total')->money('COP', locale: 'es_CO', decimalPlaces: 0)),
                TextColumn::make('bloques_estimados')
                    ->label('Alcanza para')
                    ->numeric()
                    ->suffix(' bloques')
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('sucursal')
                    ->relationship('sucursal', 'nombre'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageViajesArena::route('/'),
        ];
    }
}
