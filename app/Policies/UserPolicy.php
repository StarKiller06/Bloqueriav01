<?php

namespace App\Policies;

use App\Models\User;

/**
 * Solo el administrador de la empresa abierta gestiona sus usuarios.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, User $model): bool
    {
        return $user->esAdministrador();
    }

    /**
     * Quitar un usuario de la empresa. Nadie se quita a sí mismo, para no dejar la empresa sin administrador.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->esAdministrador() && $user->isNot($model);
    }
}
