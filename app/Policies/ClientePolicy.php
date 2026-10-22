<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

/**
 * El encargado registra y edita clientes; solo el administrador los borra.
 */
class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $user->canAccessTenant($cliente->empresa);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->canAccessTenant($cliente->empresa);
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->esAdministradorDe($cliente->empresa);
    }
}
