<?php

namespace App\Policies;

use App\Models\Trabajador;
use App\Models\User;

/**
 * El encargado registra y edita trabajadores; solo el administrador los borra.
 * Para dejar de usar a un trabajador sin perder su historial, se marca como inactivo.
 */
class TrabajadorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Trabajador $trabajador): bool
    {
        return $user->canAccessTenant($trabajador->empresa);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Trabajador $trabajador): bool
    {
        return $user->canAccessTenant($trabajador->empresa);
    }

    public function delete(User $user, Trabajador $trabajador): bool
    {
        return $user->esAdministradorDe($trabajador->empresa);
    }
}
