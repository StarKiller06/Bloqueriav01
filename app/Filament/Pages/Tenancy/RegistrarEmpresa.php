<?php

namespace App\Filament\Pages\Tenancy;

use App\Enums\Rol;
use App\Models\Empresa;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class RegistrarEmpresa extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Registrar bloquería';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre de la bloquería')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nit')
                    ->label('NIT')
                    ->required()
                    ->maxLength(20)
                    ->unique(Empresa::class, 'nit'),
            ]);
    }

    /**
     * Quien registra la bloquería queda como su administrador.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        $empresa = Empresa::create($data);

        $empresa->agregarUsuario(auth()->user(), Rol::Administrador);

        return $empresa;
    }
}
