<?php

namespace App\Filament\Resources\ComprasCemento;

use App\Filament\Resources\ComprasCemento\Pages\ManageComprasCemento;
use App\Models\CompraCemento;
use App\Models\RepartoCemento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CompraCementoResource extends Resource
{
    protected static ?string $model = CompraCemento::class;

    protected static ?string $slug = 'compras-cemento';

    protected static ?string $modelLabel = 'compra de cemento';

    protected static ?string $pluralModelLabel = 'compras de cemento';

    protected static string|UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('repartos.sucursal'))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('fecha')->orderByDesc('id'))
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('proveedor')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('bultos')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('precio_bulto')
                    ->label('Precio por bulto')
                    ->money(decimalPlaces: 0)
                    ->alignEnd(),
                TextColumn::make('total')
                    ->state(fn (CompraCemento $record): float => $record->total())
                    ->money(decimalPlaces: 0)
                    ->alignEnd(),
                TextColumn::make('reparto')
                    ->state(fn (CompraCemento $record): string => $record->repartos
                        ->map(fn (RepartoCemento $reparto): string => "{$reparto->sucursal->nombre}: {$reparto->bultos}")
                        ->join(' · ')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageComprasCemento::route('/'),
        ];
    }
}
