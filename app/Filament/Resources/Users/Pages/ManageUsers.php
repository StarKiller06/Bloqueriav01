<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Rol;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): User {
                    $rol = $data['rol'];
                    unset($data['rol']);

                    $user = User::create($data);

                    UserResource::empresaActual()->agregarUsuario($user, $rol instanceof Rol ? $rol : Rol::from($rol));

                    return $user;
                }),
        ];
    }
}
