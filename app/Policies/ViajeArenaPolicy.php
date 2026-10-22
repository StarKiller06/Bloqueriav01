<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ViajeArena;

/**
 * Se registra pero no se edita ni se borra: ya movió la caja. Un error se corrige
 * con un movimiento manual de caja o un ajuste de inventario.
 */
class ViajeArenaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ViajeArena $viajeArena): bool
    {
        return $user->canAccessTenant($viajeArena->empresa);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ViajeArena $viajeArena): bool
    {
        return false;
    }

    public function delete(User $user, ViajeArena $viajeArena): bool
    {
        return false;
    }
}
