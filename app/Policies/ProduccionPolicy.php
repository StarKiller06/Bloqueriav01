<?php

namespace App\Policies;

use App\Models\Produccion;
use App\Models\User;

/**
 * El encargado registra la producción del día. Solo el administrador anula una que no se ha pagado.
 */
class ProduccionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Produccion $produccion): bool
    {
        return $user->canAccessTenant($produccion->empresa);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Produccion $produccion): bool
    {
        return false;
    }

    public function delete(User $user, Produccion $produccion): bool
    {
        return $user->esAdministradorDe($produccion->empresa)
            && ! $produccion->estaPagada()
            && ! $produccion->empresa->mesCerrado($produccion->fecha);
    }
}
