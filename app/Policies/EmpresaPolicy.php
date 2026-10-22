<?php

namespace App\Policies;

use App\Models\Empresa;
use App\Models\User;

class EmpresaPolicy
{
    /**
     * Cualquier usuario puede registrar una bloquería nueva y queda como su administrador.
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Empresa $empresa): bool
    {
        return $user->esAdministradorDe($empresa);
    }
}
