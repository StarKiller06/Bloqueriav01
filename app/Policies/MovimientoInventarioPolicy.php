<?php

namespace App\Policies;

use App\Models\MovimientoInventario;
use App\Models\User;

/**
 * Los movimientos los crean la producción, las compras y los traslados.
 * A mano solo se hacen ajustes, y solo el administrador. Ninguno se edita ni se borra.
 */
class MovimientoInventarioPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MovimientoInventario $movimientoInventario): bool
    {
        return $user->canAccessTenant($movimientoInventario->empresa);
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, MovimientoInventario $movimientoInventario): bool
    {
        return false;
    }

    public function delete(User $user, MovimientoInventario $movimientoInventario): bool
    {
        return false;
    }
}
