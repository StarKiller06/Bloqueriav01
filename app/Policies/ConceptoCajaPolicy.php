<?php

namespace App\Policies;

use App\Models\ConceptoCaja;
use App\Models\User;

/**
 * Cualquiera crea conceptos (también desde el formulario de entradas y gastos).
 * Solo el administrador cambia o borra los manuales; los del sistema no se tocan.
 */
class ConceptoCajaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ConceptoCaja $conceptoCaja): bool
    {
        return $user->canAccessTenant($conceptoCaja->empresa);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ConceptoCaja $conceptoCaja): bool
    {
        return $user->esAdministradorDe($conceptoCaja->empresa)
            && ! $conceptoCaja->esDelSistema();
    }

    /**
     * Un concepto que ya tiene movimientos no se borra, para no dejar movimientos huérfanos.
     */
    public function delete(User $user, ConceptoCaja $conceptoCaja): bool
    {
        return $this->update($user, $conceptoCaja)
            && $conceptoCaja->movimientos()->doesntExist();
    }
}
