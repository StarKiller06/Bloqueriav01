<?php

namespace App\Policies;

use App\Models\PagoTrabajador;
use App\Models\User;

/**
 * Se registra pero no se edita ni se borra: ya movió la caja. Un error se corrige
 * con un movimiento manual de caja o un ajuste de inventario.
 */
class PagoTrabajadorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PagoTrabajador $pagoTrabajador): bool
    {
        return $user->canAccessTenant($pagoTrabajador->empresa);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PagoTrabajador $pagoTrabajador): bool
    {
        return false;
    }

    public function delete(User $user, PagoTrabajador $pagoTrabajador): bool
    {
        return false;
    }
}
