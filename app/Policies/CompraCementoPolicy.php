<?php

namespace App\Policies;

use App\Models\CompraCemento;
use App\Models\User;

/**
 * Se registra pero no se edita ni se borra: ya movió la caja. Un error se corrige
 * con un movimiento manual de caja o un ajuste de inventario.
 */
class CompraCementoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CompraCemento $compraCemento): bool
    {
        return $user->canAccessTenant($compraCemento->empresa);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CompraCemento $compraCemento): bool
    {
        return false;
    }

    public function delete(User $user, CompraCemento $compraCemento): bool
    {
        return false;
    }
}
