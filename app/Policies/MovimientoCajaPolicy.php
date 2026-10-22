<?php

namespace App\Policies;

use App\Models\MovimientoCaja;
use App\Models\User;

/**
 * Todos registran entradas y gastos. Solo el administrador corrige o borra,
 * y solo los movimientos manuales: los de ventas o pagos se corrigen desde su módulo.
 */
class MovimientoCajaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MovimientoCaja $movimientoCaja): bool
    {
        return $user->canAccessTenant($movimientoCaja->empresa);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MovimientoCaja $movimientoCaja): bool
    {
        return $user->esAdministradorDe($movimientoCaja->empresa)
            && $movimientoCaja->esManual()
            && ! $movimientoCaja->empresa->mesCerrado($movimientoCaja->fecha);
    }

    public function delete(User $user, MovimientoCaja $movimientoCaja): bool
    {
        return $this->update($user, $movimientoCaja);
    }
}
