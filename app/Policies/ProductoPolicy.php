<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

/**
 * Todos ven el precio del bloque; solo el administrador lo ajusta.
 * Los productos no se borran porque las ventas los necesitan.
 */
class ProductoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Producto $producto): bool
    {
        return $user->canAccessTenant($producto->empresa);
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Producto $producto): bool
    {
        return $user->esAdministradorDe($producto->empresa);
    }

    public function delete(User $user, Producto $producto): bool
    {
        return false;
    }
}
