<?php

namespace App\Policies;

use App\Models\Empresa;
use App\Models\User;

class EmpresaPolicy
{
    /**
     * Con el registro abierto, cualquier usuario registra una bloquería y queda como su administrador.
     * Cerrado, solo existen las bloquerías que crea UsuariosInicialesSeeder.
     */
    public function create(User $user): bool
    {
        return config('bloqueria.registro_abierto');
    }

    public function update(User $user, Empresa $empresa): bool
    {
        return $user->esAdministradorDe($empresa);
    }
}
