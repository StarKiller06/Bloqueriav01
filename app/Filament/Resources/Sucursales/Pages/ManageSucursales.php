<?php

namespace App\Filament\Resources\Sucursales\Pages;

use App\Filament\Resources\Sucursales\SucursalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSucursales extends ManageRecords
{
    protected static string $resource = SucursalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
