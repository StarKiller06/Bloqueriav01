<?php

namespace App\Filament\Resources\Trabajadores\Pages;

use App\Filament\Resources\Trabajadores\TrabajadorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTrabajadores extends ManageRecords
{
    protected static string $resource = TrabajadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
