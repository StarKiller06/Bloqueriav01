<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditarEmpresa extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Datos de la bloquería';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Empresa')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nit')
                            ->label('NIT')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make('Estándares de producción')
                    ->description('Se usan para calcular el rendimiento de cada trabajador, la arena restante y el pago por bulto.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('bloques_por_bulto')
                            ->label('Bloques por bulto de cemento')
                            ->integer()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('bloques_por_viaje')
                            ->label('Bloques por viaje de arena (7 m³)')
                            ->integer()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('tarifa_por_bulto')
                            ->label('Pago al trabajador por bulto')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->required(),
                    ]),
            ]);
    }
}
