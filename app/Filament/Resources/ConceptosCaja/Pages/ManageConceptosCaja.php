<?php

namespace App\Filament\Resources\ConceptosCaja\Pages;

use App\Filament\Resources\ConceptosCaja\ConceptoCajaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConceptosCaja extends ManageRecords
{
    protected static string $resource = ConceptoCajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
