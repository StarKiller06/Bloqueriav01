<?php

namespace App\Filament\Resources\PagosTrabajador\Pages;

use App\Filament\Resources\PagosTrabajador\PagoTrabajadorResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePagosTrabajador extends ManageRecords
{
    protected static string $resource = PagoTrabajadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PagoTrabajadorResource::pagarAction(),
        ];
    }
}
