<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venta;

/**
 * Todos venden y reciben abonos. Una venta no se edita ni se borra: si estaba mal,
 * el administrador la anula y queda registrada con su motivo.
 */
class VentaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Venta $venta): bool
    {
        return $user->canAccessTenant($venta->empresa);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Venta $venta): bool
    {
        return false;
    }

    public function delete(User $user, Venta $venta): bool
    {
        return false;
    }

    public function abonar(User $user, Venta $venta): bool
    {
        return $this->view($user, $venta) && ! $venta->estaAnulada() && (float) $venta->saldo_pendiente > 0;
    }

    public function anular(User $user, Venta $venta): bool
    {
        return $user->esAdministradorDe($venta->empresa) && ! $venta->estaAnulada();
    }
}
