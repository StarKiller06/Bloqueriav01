<?php

namespace App\Policies;

use App\Models\Sucursal;
use App\Models\User;

class SucursalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sucursal $sucursal): bool
    {
        return $user->canAccessTenant($sucursal->empresa);
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Sucursal $sucursal): bool
    {
        return $user->esAdministradorDe($sucursal->empresa);
    }

    /**
     * Una sucursal con trabajadores no se borra: primero hay que moverlos a otra.
     */
    public function delete(User $user, Sucursal $sucursal): bool
    {
        return $user->esAdministradorDe($sucursal->empresa)
            && $sucursal->trabajadores()->doesntExist();
    }
}
